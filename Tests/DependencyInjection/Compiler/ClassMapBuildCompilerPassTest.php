<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;

class ClassMapBuildCompilerPassTest extends TestCase
{

	const EMPTY_BUILDER_MAP = [
		PsrContainerInterface::class => ["service_container"],
		ContainerInterface::class => ["service_container"],
	];

	public function testProcessEmpty()
	{
		$containerBuilder = new ContainerBuilder();
		$map = new ClassMultiMap(new ContainerBuilder());
		$pass = new ClassMapBuildCompilerPass($map);

		$pass->process($containerBuilder);
		$container = $map->all();

		$this->assertSame(static::EMPTY_BUILDER_MAP, $container);
	}

	public function testProcess()
	{
		$containerBuilder = new ContainerBuilder();
		$map = new ClassMultiMap(new ContainerBuilder());
		$pass = new ClassMapBuildCompilerPass($map);

		$containerBuilder
			->setDefinition("someService", new Definition(SomeClass::class));
		$pass->process($containerBuilder);
		$this->assertSame(array_merge(static::EMPTY_BUILDER_MAP, [
			SomeInterface::class => ["someService"],
			SomeClass::class => ["someService"]
		]), $map->all());
	}

	public function testSkipEmptyClass()
	{
		$containerBuilder = new ContainerBuilder();
		$map = new ClassMultiMap(new ContainerBuilder());
		$pass = new ClassMapBuildCompilerPass($map);

		$containerBuilder
			->setDefinition("someService", new Definition());
		$pass->process($containerBuilder);
		$this->assertSame(static::EMPTY_BUILDER_MAP, $map->all());
	}

	public function testSkipAbstract()
	{
		$containerBuilder = new ContainerBuilder();
		$map = new ClassMultiMap(new ContainerBuilder());
		$pass = new ClassMapBuildCompilerPass($map);

		$containerBuilder
			->setDefinition("someService", new Definition(SomeClass::class))
			->setAbstract(true);
		$pass->process($containerBuilder);
		$this->assertSame(static::EMPTY_BUILDER_MAP, $map->all());
	}

}
