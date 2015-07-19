<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\Compiler\AutoscanCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AutoscanCompilerPassTest extends PHPUnit_Framework_TestCase
{

    /** @var AutoscanCompilerPass */
    private $autoscanCompilerPass;

    protected function setUp()
    {
        $classMultiMap = new ClassMultiMap;
        $annotationReader = new AnnotationReader;
        $this->autoscanCompilerPass = new AutoscanCompilerPass($classMultiMap, $annotationReader);
    }

    public function testProcess()
    {
        $containerBuilder = new ContainerBuilder;
        $containerBuilder->setParameter('kernel.environment', 'dev');
        $this->autoscanCompilerPass->process($containerBuilder);
    }

}
