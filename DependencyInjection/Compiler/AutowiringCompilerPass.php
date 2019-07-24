<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PhpParser;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Annotation\Value;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\Exception\AutowiringException;
use Skrz\Bundle\AutowiringBundle\Exception\MultipleValuesException;
use Skrz\Bundle\AutowiringBundle\Exception\NoValueException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Reference;
use function array_merge;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class AutowiringCompilerPass implements CompilerPassInterface
{

	/** @var ClassMultiMap */
	private $classMap;

	/** @var AnnotationReader */
	private $annotationReader;

	/** @var PhpParser */
	private $phpParser;

	/** @var string[][] */
	private $cachedUseStatements = [];

	/** @var ParameterBagInterface */
	private $parameterBag;

	public function __construct(ClassMultiMap $classMap, AnnotationReader $annotationReader, PhpParser $phpParser)
	{
		$this->classMap = $classMap;
		$this->annotationReader = $annotationReader;
		$this->phpParser = $phpParser;
		AnnotationRegistry::registerFile(__DIR__ . "/../../Annotation/Autowired.php");
	}

	public function process(ContainerBuilder $container)
	{
		$this->parameterBag = $parameterBag = $container->getParameterBag();

		try {
			$preferredServices = (array)$parameterBag->resolveValue("%autowiring.preferred_services%");
		} catch (ParameterNotFoundException $exception) {
			$preferredServices = [];
		}

		try {
			$fastAnnotationChecksRegex = "/" . implode("|", array_map(function ($s) {
					return preg_quote($s);
				}, (array)$parameterBag->resolveValue("%autowiring.fast_annotation_checks%"))) . "/";
		} catch (ParameterNotFoundException $exception) {
			$fastAnnotationChecksRegex = null;
		}

		foreach ($container->getDefinitions() as $serviceId => $definition) {
			if ($this->canDefinitionBeAutowired($serviceId, $definition) === false) {
				continue;
			}

			try {
				$className = $parameterBag->resolveValue($definition->getClass());
				$reflectionClass = $container->getReflectionClass($className, false);
				if ($reflectionClass === null) {
					continue;
				}

				$this->autowireClass(
					$className,
					$reflectionClass,
					$definition,
					$fastAnnotationChecksRegex,
					$preferredServices,
					$parameterBag
				);

				// add files to cache
				$container->addObjectResource($reflectionClass);

			} catch (AutowiringException $exception) {
				throw new AutowiringException(
					sprintf("%s (service: %s)", $exception->getMessage(), $serviceId),
					$exception->getCode(),
					$exception
				);
			}
		}
	}

	/**
	 * @param string $className
	 * @param ReflectionClass $reflectionClass
	 * @param Definition $definition
	 * @param string $fastAnnotationChecksRegex
	 * @param string[] $preferredServices
	 * @param ParameterBagInterface $parameterBag
	 */
	private function autowireClass(
		string $className,
		ReflectionClass $reflectionClass,
		Definition $definition,
		?string $fastAnnotationChecksRegex,
		array $preferredServices,
		ParameterBagInterface $parameterBag
	) {
		// constructor - autowire always
		if ($reflectionClass->getConstructor()) {
			$definition->setArguments(
				$this->autowireMethod(
					$className,
					$reflectionClass->getConstructor(),
					$definition->getArguments(),
					$preferredServices
				)
			);
		}

		if ($fastAnnotationChecksRegex === null ||
			($reflectionClass->getDocComment() &&
				preg_match($fastAnnotationChecksRegex, $reflectionClass->getDocComment()))
		) {
			// method calls @Autowired
			foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
				if ($reflectionMethod->getName() === "__construct") {
					continue;
				}

				if ($definition->hasMethodCall($reflectionMethod->getName())) {
					continue;
				}

				if (strpos($reflectionMethod->getDocComment(), "@Autowired") === false) {
					continue;
				}

				/** @var Autowired $annotation */
				$annotation = $this->annotationReader->getMethodAnnotation($reflectionMethod, Autowired::class);

				if ($annotation === null) {
					continue;
				}

				if ($annotation->name !== null) {
					throw new AutowiringException(
						sprintf(
							"@Autowired parameter can be used only on properties. %s::%s(...)",
							$className,
							$reflectionMethod->getName()
						)
					);
				}

				$definition->addMethodCall(
					$reflectionMethod->getName(),
					$this->autowireMethod(
						$className,
						$reflectionMethod,
						[],
						$preferredServices
					)
				);
			}

			// properties @Autowired, @Value
			$manualProperties = $definition->getProperties();
			foreach ($reflectionClass->getProperties() as $reflectionProperty) {
				if (isset($manualProperties[$reflectionProperty->getName()])) {
					continue;
				}

				if (strpos($reflectionProperty->getDocComment(), "@Autowired") === false &&
					strpos($reflectionProperty->getDocComment(), "@Value") === false
				) {
					continue;
				}

				$annotations = $this->annotationReader->getPropertyAnnotations($reflectionProperty);

				$autowiredAnnotation = false;
				$valueAnnotation = false;
				$incorrectUsage = false;

				foreach ($annotations as $annotation) {
					if ($annotation instanceof Autowired) {
						if ($valueAnnotation) {
							$incorrectUsage = true;
							break;
						}

						$autowiredAnnotation = true;

						try {
							if ($annotation->name !== null) {
								$definition->setProperty(
									$reflectionProperty->getName(),
									new Reference($annotation->name)
								);
							} else {
								$definition->setProperty(
									$reflectionProperty->getName(),
									$this->getValue(
										$reflectionProperty,
										$reflectionProperty->getDocComment(),
										$preferredServices
									)
								);
							}

						} catch (AutowiringException $exception) {
							throw new AutowiringException(
								sprintf(
									"%s (Property %s::$%s)",
									$exception->getMessage(),
									$className,
									$reflectionProperty->getName()
								),
								$exception->getCode(),
								$exception
							);
						}

					} elseif ($annotation instanceof Value) {
						if ($autowiredAnnotation) {
							$incorrectUsage = true;
							break;
						}

						try {
							$definition->setProperty(
								$reflectionProperty->getName(),
								$parameterBag->resolveValue($annotation->value)
							);
						} catch (RuntimeException $exception) {
							throw new AutowiringException(
								sprintf(
									"%s (Property %s::$%s)",
									$exception->getMessage(),
									$className,
									$reflectionProperty->getName()
								),
								$exception->getCode(),
								$exception
							);
						}
					}
				}

				if ($incorrectUsage) {
					throw new AutowiringException(
						sprintf(
							"Property can have either @Autowired, or @Value annotation, not both. (Property %s::$%s)",
							$className,
							$reflectionProperty->getName()
						)
					);
				}
			}

		}
	}

	/**
	 * @param string $className
	 * @param ReflectionMethod $reflectionMethod
	 * @param array $arguments
	 * @param string[] $preferredServices
	 * @return array
	 */
	private function autowireMethod(
		string $className,
		ReflectionMethod $reflectionMethod,
		array $arguments,
		array $preferredServices
	): array {
		$outputArguments = [];

		foreach ($reflectionMethod->getParameters() as $i => $reflectionProperty) {
			// intentionally array_key_exists() instead of isset(), isset() would return false if argument is null
			if (array_key_exists($i, $arguments)) {
				$outputArguments[$i] = $arguments[$i];
			} else if (array_key_exists($reflectionProperty->getName(), $arguments)) {
				$outputArguments[$i] = $arguments[$reflectionProperty->getName()];
			} else {
				try {
					$outputArguments[$i] = $this->getValue(
						$reflectionProperty,
						$reflectionMethod->getDocComment(),
						$preferredServices
					);

				} catch (AutowiringException $exception) {
					throw new AutowiringException(
						sprintf(
							"%s (%s::%s(%s$%s%s))",
							$exception->getMessage(),
							$className,
							$reflectionMethod->getName(),
							$reflectionProperty->getPosition() !== 0 ? "..., " : "",
							$reflectionProperty->getName(),
							$reflectionProperty->getPosition() < $reflectionMethod->getNumberOfParameters() - 1
								? ", ..."
								: ""
						),
						$exception->getCode(),
						$exception
					);
				}
			}
		}

		return $outputArguments;
	}

	/**
	 * @param ReflectionProperty|ReflectionParameter $target
	 * @param string $docComment
	 * @param $preferredServices
	 * @return mixed
	 */
	private function getValue($target, $docComment, $preferredServices)
	{
		$className = null;
		$isArray = false;

		// resolve class name, whether value is array
		if ($target instanceof ReflectionParameter) { // parse parameter class
			if ($target->getClass() !== null) {
				$className = $target->getClass()->getName();

			} elseif (preg_match(
				"/@param\\s+([a-zA-Z0-9\\\\_]+)(\\[\\])?(\\|[^\\s]+)*\\s+\\\$" . preg_quote($target->getName()) . "/",
				$docComment,
				$m
			)) {
				$className = $m[1];
				$isArray = isset($m[2]) && $m[2] === "[]";

			} elseif (!$target->isDefaultValueAvailable()) {
				throw new AutowiringException(sprintf(
					"Could not parse parameter type of class %s - neither type hint, nor @param annotation available.",
					$target->getDeclaringClass()->getName()
				));
			}

		} else if ($target instanceof ReflectionProperty) { // parse property class
			if (preg_match("/@var\\s+([a-zA-Z0-9\\\\_]+)(\\[\\])?/", $docComment, $m)) {
				$className = $m[1];
				$isArray = isset($m[2]) && $m[2] === "[]";

			} else {
				throw new AutowiringException(
					"Could not parse property type - no @var annotation."
				);
			}
		}

		// resolve class name to FQN
		$lowerClassName = trim(strtolower($className), "\\ \t\n");
		$useStatements = $this->getUseStatements($target);
		if (isset($useStatements[$lowerClassName])) {
			$className = $useStatements[$lowerClassName];
		} elseif (strpos($className, "\\") === false) {
			$className = $target->getDeclaringClass()->getNamespaceName() . "\\" . $className;
		}

		$className = trim($className, "\\");

		// autowire from class map
		if ($isArray) {
			return array_map(function ($serviceId) {
				return new Reference($serviceId);
			}, $this->classMap->getMulti($className));

		} elseif ($className !== null) {
			try {
				return new Reference($this->classMap->getSingle($className));

			} catch (NoValueException $exception) {
				if ($target instanceof ReflectionParameter && $target->isDefaultValueAvailable()) {
					return $target->getDefaultValue();
				} else {
					throw new AutowiringException(sprintf("Missing service of type '%s'.", $className));
				}

			} catch (MultipleValuesException $exception) {
				if (isset($preferredServices[$className])) {
					return new Reference($preferredServices[$className]);
				} else {
					throw new AutowiringException(sprintf("Multiple services of type '%s': %s", $className, $exception->getMessage()));
				}
			}

		} elseif ($target instanceof ReflectionParameter && $target->isDefaultValueAvailable()) {
			return $target->getDefaultValue();

		} else {
			throw new AutowiringException("Could not autowire.");
		}
	}

	/**
	 * @param ReflectionProperty|ReflectionParameter $target
	 * @return string[]
	 */
	private function getUseStatements($target): array
	{
		$class = $target->getDeclaringClass();
		$useStatements = $this->getClassUseStatements($class);
		foreach ($class->getTraits() as $trait) {
			if ($target instanceof ReflectionParameter &&
				$trait->hasMethod($target->getDeclaringFunction()->getName()) &&
				$target->getDeclaringFunction()->getFileName() === $trait->getFileName()
			) {
				$useStatements = array_merge($useStatements, $this->getClassUseStatements($trait));
			} else if ($target instanceof ReflectionProperty &&
				$trait->hasProperty($target->getName())
			) {
				$useStatements = array_merge($useStatements, $this->getClassUseStatements($trait));
			}
		}
		return $useStatements;
	}

	private function getClassUseStatements(ReflectionClass $reflectionClass): array
	{
		if (!isset($this->cachedUseStatements[$reflectionClass->getName()])) {
			$this->cachedUseStatements[$reflectionClass->getName()] = $this->phpParser->parseClass($reflectionClass);
		}

		return $this->cachedUseStatements[$reflectionClass->getName()];
	}

	/**
	 * @return array
	 */
	private function getIgnoredServicePatterns(): array
	{
		try {
			return (array)$this->parameterBag->resolveValue("%autowiring.ignored_services%");
		} catch (ParameterNotFoundException $exception) {
			return [];
		}
	}

	/**
	 * @param string $serviceId
	 * @param Definition $definition
	 * @return bool
	 */
	private function canDefinitionBeAutowired($serviceId, Definition $definition): bool
	{
		if (preg_match('/^\d+_[^~]++~[._a-zA-Z\d]{7}$/', $serviceId)) {
			return false;
		}

		foreach ($this->getIgnoredServicePatterns() as $pattern) {
			if (($pattern[0] === "/" && preg_match($pattern, $serviceId)) ||
				strcasecmp($serviceId, $pattern) == 0
			) {
				return false;
			}
		}

		if ($definition->isAbstract() ||
			$definition->isSynthetic() ||
			!$definition->getClass() ||
			$definition->getFactory()
		) {
			return false;
		}

		return true;
	}

}
