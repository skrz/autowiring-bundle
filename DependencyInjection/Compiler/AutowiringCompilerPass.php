<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\PhpParser;
use ReflectionClass;
use ReflectionMethod;
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

	public function __construct(ClassMultiMap $classMap, AnnotationReader $annotationReader, PhpParser $phpParser)
	{
		$this->classMap = $classMap;
		$this->annotationReader = $annotationReader;
		$this->phpParser = $phpParser;
		AnnotationRegistry::registerFile(__DIR__ . '/../../Annotation/Autowired.php');
	}

	public function process(ContainerBuilder $container)
	{
		$parameterBag = $container->getParameterBag();

		try {
			$ignoredServicePatterns = (array)$parameterBag->resolveValue("%autowiring.ignored_services%");
		} catch (ParameterNotFoundException $exception) {
			$ignoredServicePatterns = [];
		}

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
			$ignored = false;
			foreach ($ignoredServicePatterns as $pattern) {
				if (($pattern[0] === "/" && preg_match($pattern, $serviceId)) ||
					strcasecmp($serviceId, $pattern) == 0
				) {
					$ignored = true;
					break;
				}
			}

			if ($ignored ||
				$definition->isAbstract() ||
				$definition->isSynthetic() ||
				!$definition->isPublic() ||
				!$definition->getClass() ||
				$definition->getFactory() ||
				$definition->getFactoryClass(false) ||
				$definition->getFactoryService(false) ||
				$definition->getFactoryMethod(false)
			) {
				continue;
			}

			try {
				$className = $parameterBag->resolveValue($definition->getClass());
				$reflectionClass = new ReflectionClass($className);

				$this->autowireClass(
					$className,
					$reflectionClass,
					$definition,
					$fastAnnotationChecksRegex,
					$preferredServices,
					$parameterBag
				);

				// add files to cache
				$container->addClassResource($reflectionClass);

			} catch (AutowiringException $exception) {
				throw new AutowiringException(
                    sprintf("%s (service: %s)", $exception->getMessage(), $serviceId),
					$exception->getCode(), $exception
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
	private function autowireClass($className, ReflectionClass $reflectionClass, Definition $definition, $fastAnnotationChecksRegex, $preferredServices, ParameterBagInterface $parameterBag)
	{
		// constructor - autowire always
		if ($reflectionClass->getConstructor()) {
			$definition->setArguments(
				$this->autowireMethod($className, $reflectionClass->getConstructor(), $definition->getArguments(), $preferredServices)
			);
		}

		if ($fastAnnotationChecksRegex === null ||
			($reflectionClass->getDocComment() && preg_match($fastAnnotationChecksRegex, $reflectionClass->getDocComment()))
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
				if (($annotation = $this->annotationReader->getMethodAnnotation($reflectionMethod,  Autowired::class)) === null) {
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
					$this->autowireMethod($className, $reflectionMethod, $definition->getArguments(), $preferredServices)
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
										$reflectionProperty->getDeclaringClass(),
										$reflectionProperty->getDocComment(),
										null,
										null,
										false,
										null,
										$preferredServices
									)
								);
							}

						} catch (AutowiringException $exception) {
							throw new AutowiringException(
                                sprintf("%s (Property %s::$%s)", $exception->getMessage(), $className, $reflectionProperty->getName()),
								$exception->getCode(), $exception
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
                                sprintf("%s (Property %s::$%s)", $exception->getMessage(), $className, $reflectionProperty->getName()),
								$exception->getCode(), $exception
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
	private function autowireMethod($className, ReflectionMethod $reflectionMethod, array $arguments, $preferredServices)
	{
		$outputArguments = [];

		foreach ($reflectionMethod->getParameters() as $i => $reflectionProperty) {
			// intentionally array_key_exists() instead of isset(), isset() would return false if argument is null
			if (array_key_exists($i, $arguments)) {
				$outputArguments[$i] = $arguments[$i];

			} else {
				try {
					$outputArguments[$i] = $this->getValue(
						$reflectionProperty->getDeclaringClass(),
						$reflectionMethod->getDocComment(),
						$reflectionProperty->getName(), $reflectionProperty->getClass(),
						$reflectionProperty->isDefaultValueAvailable(),
						$reflectionProperty->isDefaultValueAvailable() ? $reflectionProperty->getDefaultValue() : null,
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
							$reflectionProperty->getPosition() < $reflectionMethod->getNumberOfParameters() - 1 ? ", ..." : ""
						),
						$exception->getCode(), $exception
					);
				}
			}
		}

		return $outputArguments;
	}

	/**
	 * @param ReflectionClass $reflectionClass
	 * @param string $docComment
	 * @param string $parameterName
	 * @param ReflectionClass $parameterReflectionClass
	 * @param mixed $defaultValueAvailable
	 * @param mixed $defaultValue
	 * @param $preferredServices
	 * @return mixed
	 */
	private function getValue(
        ReflectionClass $reflectionClass,
        $docComment,
        $parameterName,
        ReflectionClass $parameterReflectionClass = null,
        $defaultValueAvailable,
        $defaultValue,
        $preferredServices
    ) {
		$className = null;
		$isArray = false;

		// resolve class name, whether value is array
		if ($parameterName !== null) { // parse parameter class
			if ($parameterReflectionClass) {
				$className = $parameterReflectionClass->getName();

			} elseif (preg_match("/@param\\s+([a-zA-Z0-9\\\\_]+)(\\[\\])?(\\|[^\\s]+)*\\s+\\\$" . preg_quote($parameterName) . "/", $docComment, $m)) {
				$className = $m[1];
				$isArray = isset($m[2]) && $m[2] === "[]";

			} elseif (!$defaultValueAvailable) {
				throw new AutowiringException(
					"Could not parse parameter type - neither type hint, nor @param annotation available."
				);
			}

		} else { // parse property class
			if (preg_match("/@var\\s+([a-zA-Z0-9\\\\_]+)(\\[\\])?/", $docComment, $m)) {
				$className = $m[1];
				$isArray = isset($m[2]) && $m[2] === "[]";

			} elseif (!$defaultValueAvailable) {
				throw new AutowiringException(
					"Could not parse property type - no @var annotation."
				);
			}
		}

		// resolve class name to FQN
		$lowerClassName = trim(strtolower($className), "\\ \t\n");
		$useStatements = $this->getUseStatements($reflectionClass);
		if (isset($useStatements[$lowerClassName])) {
			$className = $useStatements[$lowerClassName];
		} elseif (strpos($className, "\\") === false) {
			$className = $reflectionClass->getNamespaceName() . "\\" . $className;
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
				if ($defaultValueAvailable) {
					return $defaultValue;
				} else {
					throw new AutowiringException(sprintf("Missing service of type '%s'.", $className));
				}

			} catch (MultipleValuesException $exception) {
				if (isset($preferredServices[$className])) {
					return new Reference($preferredServices[$className]);
				} else {
					throw new AutowiringException(sprintf("Multiple services of type '%s'.", $className));
				}
			}

		} elseif ($defaultValueAvailable) {
			return $defaultValue;

		} else {
			throw new AutowiringException("Could not autowire.");
		}
	}

    /**
     * @param ReflectionClass $reflectionClass
     * @return string[]
     */
	public function getUseStatements(ReflectionClass $reflectionClass)
	{
		if (!isset($this->cachedUseStatements[$reflectionClass->getName()])) {
			$this->cachedUseStatements[$reflectionClass->getName()] = $this->phpParser->parseClass($reflectionClass);
		}

		return $this->cachedUseStatements[$reflectionClass->getName()];
	}

}
