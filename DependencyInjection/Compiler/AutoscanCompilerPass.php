<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Skrz\Bundle\AutowiringBundle\Annotation\Component;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\Exception\AutowiringException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class AutoscanCompilerPass implements CompilerPassInterface
{

	/** @var ClassMultiMap */
	private $classMap;

	/** @var AnnotationReader */
	private $annotationReader;

	public function __construct(ClassMultiMap $classMap, AnnotationReader $annotationReader)
	{
		$this->classMap = $classMap;
		$this->annotationReader = $annotationReader;
	}

	public function process(ContainerBuilder $container)
	{
		$parameterBag = $container->getParameterBag();
		try {
			$autoscanPsr4 = (array)$parameterBag->resolveValue("%autowiring.autoscan_psr4%");
		} catch (ParameterNotFoundException $e) {
			$autoscanPsr4 = [];
		}

		try {
			$fastAnnotationChecksRegex = implode("|", array_map(function ($s) {
				return preg_quote($s);
			}, (array)$parameterBag->resolveValue("%autowiring.fast_annotation_checks%")));
		} catch (ParameterNotFoundException $e) {
			$fastAnnotationChecksRegex = null;
		}

		$env = $parameterBag->resolveValue("%kernel.environment%");

		// TODO: better error state handling
		if (empty($autoscanPsr4) || empty($fastAnnotationChecksRegex)) {
			return;
		}

		// TODO: more find methods than grep
		$grep = "egrep -lir " . escapeshellarg($fastAnnotationChecksRegex);
		foreach ($autoscanPsr4 as $ns => $dir) {
			if (!is_dir($dir)) {
				throw new AutowiringException(
					sprintf("Autoscan directory '%s' does not exits.", $dir)
				);
			}

			$autoscanPsr4[$ns] = $dir = realpath($dir);
			$grep .= " " . escapeshellarg($dir);
		}

		if (($files = shell_exec($grep)) === null) {
			throw new AutowiringException("Autoscan grep failed.");
		}

		$classNames = [];
		foreach (explode("\n", trim($files)) as $file) {
			if (substr($file, -4) !== ".php") {
				continue;
			}

			foreach ($autoscanPsr4 as $ns => $dir) {
				if (strncmp($file, $dir, strlen($dir)) === 0) {
					$fileWithoutDir = substr($file, strlen($dir), strlen($file) - strlen($dir) - 4);
					$className = $ns . str_replace("/", "\\", $fileWithoutDir);
					$classNames[$className] = $file;
					break;
				}
			}
		}

		foreach ($classNames as $className => $file) {
			try {
				new \ReflectionClass($className);
			} catch (\ReflectionException $e) {
				throw new AutowiringException(
					sprintf(
						"File '%s' does not contain class '%s', or class is not autoload-able. " .
						"Check 'autowiring.autoscan_psr4' configuration if you specified the path correctly.",
						$file,
						$className
					)
				);
			}
		}

		$classFiles = array_flip($classNames);

		foreach (get_declared_classes() as $className) {
			$serviceIds = $this->classMap->getMulti($className);
			if (!empty($serviceIds)) {
				continue;
			}

			$rc = new \ReflectionClass($className);

			if (!isset($classFiles[$rc->getFileName()])) {
				continue;
			}

			$annotations = $this->annotationReader->getClassAnnotations($rc);

			foreach ($annotations as $annotation) {
				if ($annotation instanceof Component && ($annotation->env === $env || $annotation->env === null)) {
					$serviceId = $annotation->name;

					if ($serviceId === null) {
						$annotationClassName = get_class($annotation);
						$annotationSimpleName = substr($annotationClassName, strrpos($annotationClassName, "\\") + 1);
						$classNameParts = explode("\\", $className);
						$classSimpleName = array_pop($classNameParts);
						$annotationLen = strlen($annotationSimpleName);
						if (substr($classSimpleName, -$annotationLen) === $annotationSimpleName) {
							$classSimpleName = substr($classSimpleName, 0, strlen($classSimpleName) - $annotationLen);
						}

						$middle = ".";

						do {
							$serviceId = lcfirst($annotationSimpleName) . $middle . lcfirst($classSimpleName);

							do {
								$part = array_pop($classNameParts);
							} while ($part === $annotationSimpleName && !empty($classNameParts));

							$middle = "." . lcfirst($part) . $middle;

						} while ($container->hasDefinition($serviceId) && !empty($classNameParts));
					}

					if ($container->hasDefinition($serviceId)) {
						throw new AutowiringException(
							sprintf(
								"Class '%s' cannot be added as service '%s', service ID already exists.",
								$className,
								$serviceId
							)
						);
					}

					$definition = new Definition($className);

					if ($classFiles[$rc->getFileName()] !== $className) {
						$definition->setFile($rc->getFileName());
					}

					$container->setDefinition($serviceId, $definition);
				}
			}
		}
	}

}
