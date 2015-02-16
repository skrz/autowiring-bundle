<?php
namespace Skrz\Bundle\AutowiringBundle;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
			if ($definition->isAbstract() ||
				!$definition->isPublic() ||
				!$definition->getClass()
			) {
				continue;
			}

			$this->classMap->put($parameterBag->resolveValue($definition->getClass()), $serviceId);
		}
	}

}
