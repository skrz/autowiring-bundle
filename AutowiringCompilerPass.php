<?php
namespace Skrz\Bundle\AutowiringBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Annotation\Value;
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
	}

	public function process(ContainerBuilder $container)
	{
		$parameterBag = $container->getParameterBag();

		try {
			$ignoredServicePatterns = (array)$parameterBag->resolveValue("%autowiring.ignored_services%");
		} catch (ParameterNotFoundException $e) {
			$ignoredServicePatterns = [];
		}

		try {
			$preferredServices = (array)$parameterBag->resolveValue("%autowiring.preferred_services%");
		} catch (ParameterNotFoundException $e) {
			$preferredServices = [];
		}

		try {
			$fastAnnotationChecksRegex = "/" . implode("|", array_map(function ($s) {
					return preg_quote($s);
				}, (array)$parameterBag->resolveValue("%autowiring.fast_annotation_checks%"))) . "/";
		} catch (ParameterNotFoundException $e) {
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
				$rc = new \ReflectionClass($className);

				$this->autowireClass(
					$className,
					$rc,
					$definition,
					$fastAnnotationChecksRegex,
					$preferredServices,
					$parameterBag
				);

				// add files to cache
				$container->addClassResource($rc);

			} catch (AutowiringException $e) {
				throw new AutowiringException(
					$e->getMessage() . " (service: {$serviceId})",
					$e->getCode(), $e
				);
			}
		}
	}

	private function autowireClass($className, \ReflectionClass $rc, Definition $definition, $fastAnnotationChecksRegex, $preferredServices, ParameterBagInterface $parameterBag)
	{
		// constructor - autowire always
		if ($rc->getConstructor()) {
			$definition->setArguments(
				$this->autowireMethod($className, $rc->getConstructor(), $definition->getArguments(), $preferredServices)
			);
		}

		if ($fastAnnotationChecksRegex === null ||
			($rc->getDocComment() && preg_match($fastAnnotationChecksRegex, $rc->getDocComment()))
		) {
			// method calls @Autowired
			foreach ($rc->getMethods(\ReflectionMethod::IS_PUBLIC) as $rm) {
				if ($rm->getName() === "__construct") {
					continue;
				}

				if ($definition->hasMethodCall($rm->getName())) {
					continue;
				}

				if (strpos($rm->getDocComment(), "@Autowired") === false) {
					continue;
				}

				/** @var Autowired $annotation */
				if (($annotation = $this->annotationReader->getMethodAnnotation($rm, "Skrz\\Bundle\\AutowiringBundle\\Annotation\\Autowired")) === null) {
					continue;
				}

				if ($annotation->name !== null) {
					throw new AutowiringException(
						"@Autowired parameter can be used only on properties. " .
						"{$className}::{$rm->getName()}(...)"
					);
				}

				$definition->addMethodCall(
					$rm->getName(),
					$this->autowireMethod($className, $rm, $definition->getArguments(), $preferredServices)
				);
			}

			// properties @Autowired, @Value
			$manualProperties = $definition->getProperties();
			foreach ($rc->getProperties() as $rp) {
				if (isset($manualProperties[$rp->getName()])) {
					continue;
				}

				if (strpos($rp->getDocComment(), "@Autowired") === false &&
					strpos($rp->getDocComment(), "@Value") === false
				) {
					continue;
				}

				$annotations = $this->annotationReader->getPropertyAnnotations($rp);

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
									$rp->getName(),
									new Reference($annotation->name)
								);
							} else {
								$definition->setProperty(
									$rp->getName(),
									$this->getValue(
										$rp->getDeclaringClass(),
										$rp->getDocComment(),
										null,
										null,
										false,
										null,
										$preferredServices
									)
								);
							}

						} catch (AutowiringException $e) {
							throw new AutowiringException(
								$e->getMessage() . " (Property {$className}::\${$rp->getName()})",
								$e->getCode(), $e
							);
						}

					} elseif ($annotation instanceof Value) {
						if ($autowiredAnnotation) {
							$incorrectUsage = true;
							break;
						}

						try {
							$definition->setProperty(
								$rp->getName(),
								$parameterBag->resolveValue($annotation->value)
							);
						} catch (\RuntimeException $e) {
							throw new AutowiringException(
								$e->getMessage() . " (Property {$className}::\${$rp->getName()})",
								$e->getCode(), $e
							);
						}
					}
				}

				if ($incorrectUsage) {
					throw new AutowiringException(
						"Property can have either @Autowired, or @Value annotation, not both. (Property " .
						$className . "::\$" . $rp->getName() . ")."
					);
				}
			}

		}
	}

	private function autowireMethod($className, \ReflectionMethod $rm, $arguments, $preferredServices)
	{
		$outputArguments = [];

		foreach ($rm->getParameters() as $i => $rp) {
			// intentionally array_key_exists() instead of isset(), isset() would return false if argument is null
			if (array_key_exists($i, $arguments)) {
				$outputArguments[$i] = $arguments[$i];

			} else {
				try {
					$outputArguments[$i] = $this->getValue(
						$rp->getDeclaringClass(),
						$rm->getDocComment(),
						$rp->getName(), $rp->getClass(),
						$rp->isDefaultValueAvailable(),
						$rp->isDefaultValueAvailable() ? $rp->getDefaultValue() : null,
						$preferredServices
					);

				} catch (AutowiringException $e) {
					throw new AutowiringException(
						$e->getMessage() . " ({$className}::{$rm->getName()}(" .
						($rp->getPosition() !== 0 ? "..., " : "") .
						"\${$rp->getName()}" .
						($rp->getPosition() < $rm->getNumberOfParameters() - 1 ? ", ..." : "") .
						")",
						$e->getCode(), $e
					);
				}
			}
		}

		return $outputArguments;
	}

	/**
	 * @param \ReflectionClass $rc
	 * @param string $docComment
	 * @param string $parameterName
	 * @param \ReflectionClass $parameterRc
	 * @param mixed $defaultValueAvailable
	 * @param mixed $defaultValue
	 * @param $preferredServices
	 * @internal param \ReflectionClass $rc
	 * @internal param bool $parameterIsArray
	 * @return mixed
	 */
	private function getValue($rc, $docComment, $parameterName, $parameterRc, $defaultValueAvailable, $defaultValue, $preferredServices)
	{
		$className = null;
		$isArray = false;

		// resolve class name, whether value is array
		if ($parameterName !== null) { // parse parameter class
			if ($parameterRc) {
				$className = $parameterRc->getName();

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
		$useStatements = $this->getUseStatements($rc);
		if (isset($useStatements[$lowerClassName])) {
			$className = $useStatements[$lowerClassName];
		} elseif (strpos($className, "\\") === false) {
			$className = $rc->getNamespaceName() . "\\" . $className;
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

			} catch (NoValueException $e) {
				if ($defaultValueAvailable) {
					return $defaultValue;
				} else {
					throw new AutowiringException("Missing service of type '{$className}'.");
				}

			} catch (MultipleValuesException $e) {
				if (isset($preferredServices[$className])) {
					return new Reference($preferredServices[$className]);
				} else {
					throw new AutowiringException("Multiple services of type '{$className}'.");
				}
			}

		} elseif ($defaultValueAvailable) {
			return $defaultValue;

		} else {
			throw new AutowiringException("Could not autowire.");
		}
	}

	public function getUseStatements(\ReflectionClass $rc)
	{
		if (!isset($this->cachedUseStatements[$rc->getName()])) {
			$this->cachedUseStatements[$rc->getName()] = $this->phpParser->parseClass($rc);
		}

		return $this->cachedUseStatements[$rc->getName()];
	}

}
