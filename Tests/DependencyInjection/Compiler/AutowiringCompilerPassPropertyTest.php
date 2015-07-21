<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PhpParser;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutowiringCompilerPass;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\AutowiredPropertyClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\SomeClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AutowiringCompilerPassPropertyTest extends PHPUnit_Framework_TestCase
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

    public function testAutowireConstructor()
    {
        $containerBuilder = new ContainerBuilder;
        $autowiredServiceDefinition = $containerBuilder->setDefinition('autowiredService', new Definition(AutowiredPropertyClass::class));
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class));

        $this->assertSame([], $autowiredServiceDefinition->getProperties());

        $this->classMapBuildCompilerPass->process($containerBuilder);
        $this->autowiringCompilerPass->process($containerBuilder);

        $reference = $autowiredServiceDefinition->getProperties()['property'];
        $this->assertInstanceOf(Reference::class, $reference);
        $this->assertSame('someservice', (string) $reference);
    }

}
