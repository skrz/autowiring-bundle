<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;

class ClassMapBuildCompilerPassTest extends PHPUnit_Framework_TestCase
{

	/** @var string */
	const SOME_CLASS_NAME = "Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\ClassMultipleMapSource\\SomeClass";

	/** @var ClassMultiMap */
	private $classMultiMap;

	/** @var ClassMapBuildCompilerPass */
	private $classMapBuildCompilerPass;

	protected function setUp()
	{
		$this->classMultiMap = new ClassMultiMap;
		$this->classMapBuildCompilerPass = new ClassMapBuildCompilerPass($this->classMultiMap);
	}

	public function testProcessEmpty()
	{
		$containerBuilder = new ContainerBuilder;
		$this->classMapBuildCompilerPass->process($containerBuilder);
		$container = $this->getClassMapBuildClasses();

		$this->assertSame([], $container);
	}

	public function testProcess()
	{
		$containerBuilder = new ContainerBuilder;
		$containerBuilder->setDefinition("someService", new Definition(self::SOME_CLASS_NAME));
		$this->classMapBuildCompilerPass->process($containerBuilder);
		$serviceName = Kernel::VERSION_ID >= 30300 ? "someService" : "someservice";

		$this->assertSame([
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\ClassMultipleMapSource\\SomeInterface" => [$serviceName],
			self::SOME_CLASS_NAME => [$serviceName]
		], $this->getClassMapBuildClasses());
	}

	public function testSkipPrivate()
	{
		$containerBuilder = new ContainerBuilder;
		$containerBuilder->setDefinition("someService", new Definition(self::SOME_CLASS_NAME))
			->setPublic(false);
		$this->classMapBuildCompilerPass->process($containerBuilder);

		$this->assertSame([], $this->getClassMapBuildClasses());
	}

	public function testSkipEmptyClass()
	{
		$containerBuilder = new ContainerBuilder;
		$containerBuilder->setDefinition("someService", new Definition);
		$this->classMapBuildCompilerPass->process($containerBuilder);

		$this->assertSame([], $this->getClassMapBuildClasses());
	}

	public function testSkipAbstract()
	{
		$containerBuilder = new ContainerBuilder;
		$containerBuilder->setDefinition("someService", new Definition(self::SOME_CLASS_NAME))
			->setAbstract(true);
		$this->classMapBuildCompilerPass->process($containerBuilder);

		$this->assertSame([], $this->getClassMapBuildClasses());
	}

	/**
	 * @return string[]
	 */
	private function getClassMapBuildClasses()
	{
		$classes = PHPUnit_Framework_Assert::getObjectAttribute($this->classMultiMap, "classes");
		//since SF3.3 container aliases are added in constructor
		unset($classes[PsrContainerInterface::class]);
		unset($classes[ContainerInterface::class]);

		return $classes;
	}

}
