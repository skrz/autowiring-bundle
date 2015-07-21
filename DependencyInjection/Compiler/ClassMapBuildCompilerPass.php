<?php

/**
 * This file is part of the AutowiringBundle.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
			if ($this->canBeAdded($definition) === FALSE) {
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
			return FALSE;
		}

		if (!$definition->isPublic()) {
			return FALSE;
		}

		if (!$definition->getClass()) {
			return FALSE;
		}

		return TRUE;
	}

}
