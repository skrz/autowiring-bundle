<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassTest extends PHPUnit_Framework_TestCase
{

	/** @var string */
	const AUTOWIRED_CLASS_NAME = "Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\AutowiredClass";

	/** @var ClassMapBuildCompilerPass */
	private $classMapBuildCompilerPass;

	/** @var AutowiringCompilerPass */
	private $autowiringCompilerPass;

	protected function setUp()
	{
		$classMultiMap = new ClassMultiMap;
		$annotationReader = new AnnotationReader;
		$phpParser = new PhpParser;
		// todo: hidden dependency, is logically required by AutowiringCompilerPass
		$this->classMapBuildCompilerPass = new ClassMapBuildCompilerPass($classMultiMap);
		$this->autowiringCompilerPass = new AutowiringCompilerPass($classMultiMap, $annotationReader, $phpParser);
	}

	public function testAutowireConstructorWithMissingClass()
	{
		$containerBuilder = new ContainerBuilder;
		$containerBuilder->setDefinition("autowiredService", new Definition(self::AUTOWIRED_CLASS_NAME));
		$this->setExpectedException("Skrz\\Bundle\\AutowiringBundle\\Exception\\AutowiringException");
		$this->classMapBuildCompilerPass->process($containerBuilder);
		$this->autowiringCompilerPass->process($containerBuilder);
	}

	public function testAutowireConstructor()
	{
		$containerBuilder = new ContainerBuilder;
		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(self::AUTOWIRED_CLASS_NAME));
		$containerBuilder->setDefinition("someService", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\SomeClass"
		));

		$this->assertSame([], $autowiredServiceDefinition->getArguments());

		$this->classMapBuildCompilerPass->process($containerBuilder);
		$this->autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf("Symfony\\Component\\DependencyInjection\\Reference", $reference);
		$this->assertSame("someservice", (string) $reference);
	}

	public function testAutowireConstructorWithInterface()
	{
		$containerBuilder = new ContainerBuilder;
		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\InterfaceAutowiredClass"
		));
		$containerBuilder->setDefinition("someService", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\SomeClass"
		));

		$this->assertSame([], $autowiredServiceDefinition->getArguments());

		$this->classMapBuildCompilerPass->process($containerBuilder);
		$this->autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf("Symfony\\Component\\DependencyInjection\\Reference", $reference);
		$this->assertSame("someservice", (string) $reference);
	}

	public function testAutowireConstructorWithInterfaceOptionally()
	{
		$containerBuilder = new ContainerBuilder;
		$service2Ref = new Reference('someService2');

		$autowiredServiceDefinition = $containerBuilder->setDefinition("autowiredService", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\OptionalAutowiredClass",
			[
				'someClass2' => $service2Ref,
			]
		));
		$containerBuilder->setDefinition("someService", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\SomeClass"
		));
		$containerBuilder->setDefinition("someService2", new Definition(
			"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\SomeClass2"
		));

		$containerBuilder->getParameterBag()->add([
			'autowiring.preferred_services' => [
				"Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\Compiler\\AutowiringCompilerPassSource\\SomeInterface" => 'someService',
			]
		]);

		$this->assertSame(
			[
				'someClass2' => $service2Ref,
			],
			$autowiredServiceDefinition->getArguments()
		);

		$this->classMapBuildCompilerPass->process($containerBuilder);
		$this->autowiringCompilerPass->process($containerBuilder);

		$arguments = $autowiredServiceDefinition->getArguments();
		$this->assertNotSame([], $arguments);

		/** @var Reference $reference */
		$reference = $arguments[0];
		$this->assertInstanceOf("Symfony\\Component\\DependencyInjection\\Reference", $reference);
		$this->assertSame("someservice", (string) $reference);

		$reference = $arguments[1];
		$this->assertInstanceOf("Symfony\\Component\\DependencyInjection\\Reference", $reference);
		$this->assertSame("someservice2", (string) $reference);
	}
}
