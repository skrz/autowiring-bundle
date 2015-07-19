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

	/** @var array */
  	private $classes = array();

	/**
	 * @param string $className
	 * @param string $value
	 */
	public function put($className, $value)
	{
		$rc = new ReflectionClass($className);

		foreach ($rc->getInterfaceNames() as $interfaceName) {
			if (!isset($this->classes[$interfaceName])) {
				$this->classes[$interfaceName] = array();
			}
			$this->classes[$interfaceName][] = $value;
		}

		do {
			if (!isset($this->classes[$rc->getName()])) {
				$this->classes[$rc->getName()] = array();
			}
			$this->classes[$rc->getName()][] = $value;
		} while ($rc = $rc->getParentClass());
	}

	/**
	 * @param string $className
	 * @return string
	 */
	public function getSingle($className)
	{
		if (!isset($this->classes[$className])) {
			throw new NoValueException("Key '{$className}'.");
		}

		$values = $this->classes[$className];

		if (count($values) > 1) {
			throw new MultipleValuesException("Key '{$className}' - values: " . json_encode($values) . ".");
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
			return array();
		}

		return $this->classes[$className];
	}

}
