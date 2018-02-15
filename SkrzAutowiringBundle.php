<?php
namespace Skrz\Bundle\AutowiringBundle;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
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
		$annotationReader = new AnnotationReader();
		$autowiringClassMap = new ClassMultiMap($container);

		$container->addCompilerPass(
			new ClassMapBuildCompilerPass($autowiringClassMap),
			PassConfig::TYPE_OPTIMIZE
		);

		$container->addCompilerPass(
			new AutowiringCompilerPass($autowiringClassMap, $annotationReader, new PhpParser()),
			PassConfig::TYPE_OPTIMIZE
		);
	}

}
