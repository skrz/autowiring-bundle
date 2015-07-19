<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Exception\AutowiringException;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\InterfaceAutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassTest extends PHPUnit_Framework_TestCase
{

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
        $containerBuilder->setDefinition('autowiredService', new Definition(AutowiredClass::class));
        $this->setExpectedException(AutowiringException::class);
        $this->classMapBuildCompilerPass->process($containerBuilder);
        $this->autowiringCompilerPass->process($containerBuilder);
    }

    public function testAutowireConstructor()
    {
        $containerBuilder = new ContainerBuilder;
        $autowiredServiceDefinition = $containerBuilder->setDefinition('autowiredService', new Definition(AutowiredClass::class));
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class));

        $this->assertSame([], $autowiredServiceDefinition->getArguments());

        $this->classMapBuildCompilerPass->process($containerBuilder);
        $this->autowiringCompilerPass->process($containerBuilder);

        $arguments = $autowiredServiceDefinition->getArguments();
        $this->assertNotSame([], $arguments);

        /** @var Reference $reference */
        $reference = $arguments[0];
        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame('someservice', (string) $reference);
    }

    public function testAutowireConstructorWithInterface()
    {
        $containerBuilder = new ContainerBuilder;
        $autowiredServiceDefinition = $containerBuilder->setDefinition('autowiredService', new Definition(InterfaceAutowiredClass::class));
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class));

        $this->assertSame([], $autowiredServiceDefinition->getArguments());

        $this->classMapBuildCompilerPass->process($containerBuilder);
        $this->autowiringCompilerPass->process($containerBuilder);

        $arguments = $autowiredServiceDefinition->getArguments();
        $this->assertNotSame([], $arguments);

        /** @var Reference $reference */
        $reference = $arguments[0];
        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame('someservice', (string) $reference);
    }

}
