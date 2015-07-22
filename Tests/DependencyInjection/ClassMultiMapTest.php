<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeClass;

class ClassMultiMapTest extends PHPUnit_Framework_TestCase
{

    /** @var string */
    const SOME_CLASS_NAME = "Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\ClassMultipleMapSource\\SomeClass";

    /** @var ClassMultiMap */
    private $classMultiMap;

    protected function setUp()
    {
        $this->classMultiMap = new ClassMultiMap;
    }

    public function testGetSingleForSimpleClass()
    {
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someValue");
        $this->assertSame(
            "someValue",
            $this->classMultiMap->getSingle(self::SOME_CLASS_NAME)
        );
    }

    public function testGetSingleForSimpleForInterface()
    {
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someValue");
        $this->assertSame("someValue", $this->classMultiMap->getSingle(
            "Skrz\\Bundle\\AutowiringBundle\\Tests\\DependencyInjection\\ClassMultipleMapSource\\SomeInterface"
        ));
    }

    public function testGetSingleForNonAddedClass()
    {
        $this->setExpectedException("Skrz\\Bundle\\AutowiringBundle\\Exception\\NoValueException");
        $this->classMultiMap->getSingle(self::SOME_CLASS_NAME);
    }

    public function testGetSingleForClassAddedTwice()
    {
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someValue");
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someOtherValue");
        $this->setExpectedException("Skrz\\Bundle\\AutowiringBundle\\Exception\\MultipleValuesException");
        $this->classMultiMap->getSingle(self::SOME_CLASS_NAME);
    }

    public function testGetMultiForNonAddedClass()
    {
        $this->assertSame([], $this->classMultiMap->getMulti(self::SOME_CLASS_NAME));
    }

    public function testGetMultiForClassAddedTwice()
    {
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someValue");
        $this->classMultiMap->put(self::SOME_CLASS_NAME, "someOtherValue");
        $this->assertSame(["someValue", "someOtherValue"], $this->classMultiMap->getMulti(self::SOME_CLASS_NAME));
    }

}
