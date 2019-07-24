<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClassOverridesMethodTrait;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClassUsesMethodTrait;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo\Bar;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo2\Bar2;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassMethodTest extends TestCase
{

	public function testAutowireTraitMethod()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);

		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$definition = $containerBuilder->setDefinition("service", new Definition(AutowiredClassUsesMethodTrait::class));
		$containerBuilder->setDefinition("bar", new Definition(Bar::class));

		$this->assertSame([], $definition->getMethodCalls());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$calls = $definition->getMethodCalls();
		$this->assertCount(1, $calls);
		$this->assertSame("setBar", $calls[0][0]);
		$this->assertCount(1, $calls[0][1]);
		$this->assertInstanceOf(Reference::class, $calls[0][1][0]);
		$this->assertSame("bar", (string)$calls[0][1][0]);
	}

	public function testAutowireOverriddenTraitMethod()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);

		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$definition = $containerBuilder->setDefinition("service", new Definition(AutowiredClassOverridesMethodTrait::class));
		$containerBuilder->setDefinition("bar", new Definition(Bar::class));
		$containerBuilder->setDefinition("bar2", new Definition(Bar2::class));

		$this->assertSame([], $definition->getMethodCalls());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$calls = $definition->getMethodCalls();
		$this->assertCount(1, $calls);
		$this->assertSame("setBar", $calls[0][0]);
		$this->assertCount(1, $calls[0][1]);
		$this->assertInstanceOf(Reference::class, $calls[0][1][0]);
		$this->assertSame("bar2", (string)$calls[0][1][0]);
	}

}
