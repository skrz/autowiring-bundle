<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Exception\AutowiringException;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\InterfaceAutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\OptionalAutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeClass2;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassTest extends TestCase
{

	public function testAutowireConstructorWithMissingClass()
	{
		$this->expectException(AutowiringException::class);

		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);
		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());
		$containerBuilder->setDefinition("autowiredService", new Definition(AutowiredClass::class));
		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);
	}

	public function testAutowireConstructor()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);
		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(AutowiredClass::class));
		$containerBuilder->setDefinition("someService", new Definition(SomeClass::class));

		$this->assertSame([], $autowiredServiceDefinition->getArguments());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("someService", (string)$reference);
	}

	public function testAutowireConstructorWithInterface()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);
		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(InterfaceAutowiredClass::class));
		$containerBuilder->setDefinition("someService", new Definition(SomeClass::class));

		$this->assertSame([], $autowiredServiceDefinition->getArguments());

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("someService", (string)$reference);
	}

	public function testAutowireConstructorWithInterfaceOptionally()
	{
		$containerBuilder = new ContainerBuilder();
		$classMultiMap = new ClassMultiMap($containerBuilder);
		$classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, new AnnotationReader(), new PhpParser());

		$service2Ref = new Reference("someService2");

		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(
			OptionalAutowiredClass::class,
			[
				"someClass2" => $service2Ref,
			]
		));
		$containerBuilder->setDefinition("someService", new Definition(SomeClass::class));
		$containerBuilder->setDefinition("someService2", new Definition(SomeClass2::class));

		$containerBuilder->getParameterBag()->add([
			"autowiring.preferred_services" => [
				SomeInterface::class => "someService",
			]
		]);

		$this->assertSame(
			[
				"someClass2" => $service2Ref,
			],
			$autowiredServiceDefinition->getArguments()
		);

		$classMapBuildCompilerPass->process($containerBuilder);
		$autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("someService", (string)$reference);

		$reference = $arguments[1];
		$this->assertInstanceOf(Reference::class, $reference);
		$this->assertSame("someService2", (string)$reference);
	}

}
