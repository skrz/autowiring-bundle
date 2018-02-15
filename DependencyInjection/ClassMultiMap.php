<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection;

use Skrz\Bundle\AutowiringBundle\Exception\MultipleValuesException;
use Skrz\Bundle\AutowiringBundle\Exception\NoValueException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Maps from class name, all its parents, and implemented interfaces to certain value
 *
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class ClassMultiMap
{

	/** @var ContainerBuilder */
	private $containerBuilder;

	/** @var string[][] */
	private $classes = [];

	public function __construct(ContainerBuilder $containerBuilder)
	{
		$this->containerBuilder = $containerBuilder;
	}

	/**
	 * @param string $className
	 * @param string $value
	 * @return void
	 */
	public function put(string $className, string $value)
	{
		$reflectionClass = $this->containerBuilder->getReflectionClass($className, false);
		if ($reflectionClass === null) {
			return;
		}

		foreach ($reflectionClass->getInterfaceNames() as $interfaceName) {
			if (!isset($this->classes[$interfaceName])) {
				$this->classes[$interfaceName] = [];
			}
			$this->classes[$interfaceName][] = $value;
		}

		do {
			if (!isset($this->classes[$reflectionClass->getName()])) {
				$this->classes[$reflectionClass->getName()] = [];
			}
			$this->classes[$reflectionClass->getName()][] = $value;
		} while ($reflectionClass = $reflectionClass->getParentClass());
	}

	/**
	 * @param string $className
	 * @return string
	 */
	public function getSingle(string $className): string
	{
		if (!isset($this->classes[$className])) {
			throw new NoValueException(sprintf("Key '%s'.", $className));
		}

		$values = $this->classes[$className];

		if (count($values) > 1) {
			throw new MultipleValuesException(sprintf("Key '%s' - values: '%s'", $className, json_encode($values)));
		}

		return reset($values);
	}

	/**
	 * @param string $className
	 * @return string[]
	 */
	public function getMulti($className): array
	{
		if (!isset($this->classes[$className])) {
			return [];
		}

		return $this->classes[$className];
	}

	/**
	 * @return string[][]
	 */
	public function all(): array
	{
		return $this->classes;
	}

}
