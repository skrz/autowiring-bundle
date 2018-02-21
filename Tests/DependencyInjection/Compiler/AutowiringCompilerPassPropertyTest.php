<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredPropertyClass;
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

}
