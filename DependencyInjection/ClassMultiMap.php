<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection;

use ReflectionClass;
use Skrz\Bundle\AutowiringBundle\Exception\MultipleValuesException;
use Skrz\Bundle\AutowiringBundle\Exception\NoValueException;

/**
 * Maps from class name, all its parents, and implemented interfaces to certain value
 *
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class ClassMultiMap
{

	/** @var string[] */
	private $classes = [];

	/**
	 * @param string $className
	 * @param string $value
	 */
	public function put($className, $value)
	{
		$reflectionClass = new ReflectionClass($className);

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
	public function getSingle($className)
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
	public function getMulti($className)
	{
		if (!isset($this->classes[$className])) {
			return [];
		}

		return $this->classes[$className];
	}

}
