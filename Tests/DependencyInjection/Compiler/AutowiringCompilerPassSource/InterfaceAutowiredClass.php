<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

class InterfaceAutowiredClass
{

    public function __construct(SomeInterface $someClass)
    {
    }

}
