<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClassOverridesPropertyTrait;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClassUsesPropertyTrait;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredPropertyClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo\Bar;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo2\Bar2;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassPropertyTest extends TestCase
{

	public function testAutowireProperty()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);

		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(AutowiredPropertyClass::class));
		$containerBuilder->setDefinition("someService", new Definition(SomeClass::class));

		$this->assertSame([], $autowiredServiceDefinition->getProperties());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$reference = $autowiredServiceDefinition->getProperties()["property"];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("someService", (string)$reference);
	}

	public function testAutowireTraitProperty()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);

		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$autowiredServiceDefinition = $containerBuilder->setDefinition("service", new Definition(AutowiredClassUsesPropertyTrait::class));
		$containerBuilder->setDefinition("bar", new Definition(Bar::class));

		$this->assertSame([], $autowiredServiceDefinition->getProperties());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$reference = $autowiredServiceDefinition->getProperties()["property"];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("bar", (string)$reference);
	}

	public function testAutowireOverriddenTraitProperty()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);

		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$autowiredServiceDefinition = $containerBuilder->setDefinition("service", new Definition(AutowiredClassOverridesPropertyTrait::class));
		$containerBuilder->setDefinition("bar", new Definition(Bar::class));
		$containerBuilder->setDefinition("bar2", new Definition(Bar2::class));

		$this->assertSame([], $autowiredServiceDefinition->getProperties());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$reference = $autowiredServiceDefinition->getProperties()["property"];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("bar2", (string)$reference);
	}

}
