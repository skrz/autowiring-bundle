<?php
namespace Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler;

use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ClassMapBuildCompilerPass implements CompilerPassInterface
{

	/** @var ClassMultiMap */
	private $classMap;

	public function __construct(ClassMultiMap $typeMap)
	{
		$this->classMap = $typeMap;
	}

	public function process(ContainerBuilder $container)
	{
		$parameterBag = $container->getParameterBag();

		foreach ($container->getDefinitions() as $serviceId => $definition) {
			if (!$this->canBeAdded($definition)) {
				continue;
			}

			$this->classMap->put($parameterBag->resolveValue($definition->getClass()), $serviceId);
		}
	}

	/**
	 * @return bool
	 */
	private function canBeAdded(Definition $definition)
	{
		if ($definition->isAbstract()) {
			return false;
		}

		if (!$definition->getClass()) {
			return false;
		}

		if (!class_exists($definition->getClass())) {
			return false;
		}

		return true;
	}

}
