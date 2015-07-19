<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\Exception\MultipleValuesException;
use Skrz\Bundle\AutowiringBundle\Exception\NoValueException;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeInterface;

class ClassMultiMapTest extends PHPUnit_Framework_TestCase
{

    /** @var ClassMultiMap */
    private $classMultiMap;

    protected function setUp()
    {
        $this->classMultiMap = new ClassMultiMap;
    }

    public function testGetSingleForSimpleClass()
    {
        $this->classMultiMap->put(SomeClass::class, 'someValue');
        $this->assertSame('someValue', $this->classMultiMap->getSingle(SomeClass::class));
    }

    public function testGetSingleForSimpleForInterface()
    {
        $this->classMultiMap->put(SomeClass::class, 'someValue');
        $this->assertSame('someValue', $this->classMultiMap->getSingle(SomeInterface::class));
    }

    public function testGetSingleForNonAddedClass()
    {
        $this->setExpectedException(NoValueException::class);
        $this->classMultiMap->getSingle(SomeClass::class);
    }

    public function testGetSingleForClassAddedTwice()
    {
        $this->classMultiMap->put(SomeClass::class, 'someValue');
        $this->classMultiMap->put(SomeClass::class, 'someOtherValue');
        $this->setExpectedException(MultipleValuesException::class);
        $this->classMultiMap->getSingle(SomeClass::class);
    }

    public function testGetMultiForNonAddedClass()
    {
        $this->assertSame([], $this->classMultiMap->getMulti(SomeClass::class));
    }

    public function testGetMultiForClassAddedTwice()
    {
        $this->classMultiMap->put(SomeClass::class, 'someValue');
        $this->classMultiMap->put(SomeClass::class, 'someOtherValue');
        $this->assertSame(['someValue', 'someOtherValue'], $this->classMultiMap->getMulti(SomeClass::class));
    }

}
