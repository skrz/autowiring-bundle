<?php
namespace Skrz\Bundle\AutowiringBundle\Tests;

use PHPUnit_Framework_TestCase;
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
			"Skrz\\Bundle\\AutowiringBundle\\DependencyInjection\\SkrzAutowiringExtension",
			$this->skrzAutowiringBundle->getContainerExtension()
		);
	}

	public function testBuild()
	{
		$containerBuilder = new ContainerBuilder;
		$this->skrzAutowiringBundle->build($containerBuilder);
		$passConfig = $containerBuilder->getCompiler()->getPassConfig();

		$beforeOptimizationPasses = $passConfig->getBeforeOptimizationPasses();
		$this->assertInstanceOf("Skrz\\Bundle\\AutowiringBundle\\DependencyInjection\\Compiler\\ClassMapBuildCompilerPass", $beforeOptimizationPasses[0]);
		$this->assertInstanceOf("Skrz\\Bundle\\AutowiringBundle\\DependencyInjection\\Compiler\\AutoscanCompilerPass", $beforeOptimizationPasses[1]);

		$afterRemovingPasses = $passConfig->getAfterRemovingPasses();
		$this->assertInstanceOf("Skrz\\Bundle\\AutowiringBundle\\DependencyInjection\\Compiler\\ClassMapBuildCompilerPass", $afterRemovingPasses[0]);
		$this->assertInstanceOf("Skrz\\Bundle\\AutowiringBundle\\DependencyInjection\\Compiler\\AutowiringCompilerPass", $afterRemovingPasses[1]);
	}

}
