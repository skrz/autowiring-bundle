<?php
namespace Skrz\Bundle\AutowiringBundle\Tests;

use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutoscanCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Skrz\Bundle\AutowiringBundle\SkrzAutowiringBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SkrzAutowiringBundleTest extends PHPUnit_Framework_TestCase
{

	/** @var SkrzAutowiringBundle */
	private $skrzAutowiringBundle;

	protected function setUp()
	{
		$this->skrzAutowiringBundle = new SkrzAutowiringBundle;
	}

    public function testGetContainerExtension()
    {
        $this->assertInstanceOf(
            SkrzAutowiringExtension::class,
            $this->skrzAutowiringBundle->getContainerExtension()
        );
    }

    public function testBuild()
    {
        $containerBuilder = new ContainerBuilder;
        $this->skrzAutowiringBundle->build($containerBuilder);
        $passConfig = $containerBuilder->getCompiler()->getPassConfig();

        $beforeOptimizationPasses = $passConfig->getBeforeOptimizationPasses();
        $this->assertInstanceOf(ClassMapBuildCompilerPass::class, $beforeOptimizationPasses[0]);
        $this->assertInstanceOf(AutoscanCompilerPass::class, $beforeOptimizationPasses[1]);

        $afterRemovingPasses = $passConfig->getAfterRemovingPasses();
        $this->assertInstanceOf(ClassMapBuildCompilerPass::class, $afterRemovingPasses[0]);
        $this->assertInstanceOf(AutowiringCompilerPass::class, $afterRemovingPasses[1]);
    }

}
