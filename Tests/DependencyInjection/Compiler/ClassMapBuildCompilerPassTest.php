<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use PHPUnit_Framework_Assert;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\ClassMapBuildCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

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

        $this->assertSame([], $this->getClassMapBuildClasses());
    }

    public function testProcess()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition(self::SOME_CLASS_NAME));
        $this->classMapBuildCompilerPass->process($containerBuilder);

        $this->assertSame([
            "Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\ClassMultipleMapSource\\SomeInterface" => ['someservice'],
            self::SOME_CLASS_NAME => ['someservice']
        ], $this->getClassMapBuildClasses());
    }

    public function testSkipPrivate()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setDefinition('someService', new Definition(self::SOME_CLASS_NAME))
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
        $containerBuilder->setDefinition('someService', new Definition(self::SOME_CLASS_NAME))
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
