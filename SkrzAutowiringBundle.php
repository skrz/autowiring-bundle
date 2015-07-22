<?php
namespace Skrz\Bundle\AutowiringBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutoscanCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class SkrzAutowiringBundle extends Bundle
{

	/**
	 * {@inheritDoc}
	 */
	public function getContainerExtension()
	{
		if ($this->extension === null) {
			$this->extension = new SkrzAutowiringExtension();
		}

		return $this->extension;
	}

	public function build(ContainerBuilder $container)
	{
		$annotationReader = new AnnotationReader;
		$autoscanClassMap = new ClassMultiMap;

		$container->addCompilerPass(
			new ClassMapBuildCompilerPass($autoscanClassMap),
			PassConfig::TYPE_BEFORE_OPTIMIZATION
		);

		$container->addCompilerPass(
			new AutoscanCompilerPass($autoscanClassMap, $annotationReader),
			PassConfig::TYPE_BEFORE_OPTIMIZATION
		);

		$autowiringClassMap = new ClassMultiMap;

		$container->addCompilerPass(
			new ClassMapBuildCompilerPass($autowiringClassMap),
			PassConfig::TYPE_AFTER_REMOVING
		);

		$container->addCompilerPass(
			new AutowiringCompilerPass($autowiringClassMap, $annotationReader, new PhpParser),
			PassConfig::TYPE_AFTER_REMOVING
		);
	}

}
