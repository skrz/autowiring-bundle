<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ClassMapBuildCompilerPassTest extends PHPUnit_Framework_TestCase
{

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

        $this->assertSame([], $this->getClassMapBuildClasses());
    }

    public function testProcess()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class));
        $this->classMapBuildCompilerPass->process($containerBuilder);

        $this->assertSame([
            SomeInterface::class => ['someservice'],
            SomeClass::class => ['someservice']
        ], $this->getClassMapBuildClasses());
    }

    public function testSkipPrivate()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class))
            ->setPublic(FALSE);
        $this->classMapBuildCompilerPass->process($containerBuilder);

        $this->assertSame([], $this->getClassMapBuildClasses());
    }

    public function testSkipEmptyClass()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition);
        $this->classMapBuildCompilerPass->process($containerBuilder);

        $this->assertSame([], $this->getClassMapBuildClasses());
    }

    public function testSkipAbstract()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition(SomeClass::class))
            ->setAbstract(TRUE);
        $this->classMapBuildCompilerPass->process($containerBuilder);

        $this->assertSame([], $this->getClassMapBuildClasses());
    }

    /**
     * @return string[]
     */
    private function getClassMapBuildClasses()
    {
        return PHPUnit_Framework_Assert::getObjectAttribute($this->classMultiMap, 'classes');
    }

}
