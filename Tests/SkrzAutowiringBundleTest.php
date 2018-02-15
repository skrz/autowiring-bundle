<?php
namespace Skrz\Bundle\AutowiringBundle\Tests;

use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Skrz\Bundle\AutowiringBundle\SkrzAutowiringBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SkrzAutowiringBundleTest extends TestCase
{

	/** @var SkrzAutowiringBundle */
	private $bundle;

	protected function setUp()
	{
		$this->bundle = new SkrzAutowiringBundle();
	}

	public function testGetContainerExtension()
	{
		$this->assertInstanceOf(SkrzAutowiringExtension::class, $this->bundle->getContainerExtension());
	}

	public function testBuild()
	{
		$containerBuilder = new ContainerBuilder();
		$this->bundle->build($containerBuilder);
		$passConfig = $containerBuilder->getCompiler()->getPassConfig();

		$passes = $passConfig->getOptimizationPasses();

		$classMapBuilderFound = 0;
		$autowiringCompilerPassFound = 0;
		foreach ($passes as $pass) {
			if ($pass instanceof AutowiringCompilerPass) {
				++$autowiringCompilerPassFound;
			} else if ($pass instanceof ClassMapBuildCompilerPass) {
				++$classMapBuilderFound;
			}
		}

		$this->assertEquals(1, $classMapBuilderFound, sprintf("Compiler pass [%s] should be registered.", ClassMapBuildCompilerPass::class));
		$this->assertEquals(1, $autowiringCompilerPassFound, sprintf("Compiler pass [%s] should be registered.", AutowiringCompilerPass::class));
	}

}
