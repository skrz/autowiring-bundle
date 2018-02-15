<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\ClassMultiMap;
use Skrz\Bundle\AutowiringBundle\Exception\MultipleValuesException;
use Skrz\Bundle\AutowiringBundle\Exception\NoValueException;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeClass;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\ClassMultipleMapSource\SomeInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassMultiMapTest extends TestCase
{

	public function testGetSingleForSimpleClass()
	{
		$map = new ClassMultiMap(new ContainerBuilder());

		$map->put(SomeClass::class, "someValue");
		$this->assertSame(
			"someValue",
			$map->getSingle(SomeClass::class)
		);
	}

	public function testGetSingleForSimpleForInterface()
	{
		$map = new ClassMultiMap(new ContainerBuilder());

		$map->put(SomeClass::class, "someValue");
		$this->assertSame("someValue", $map->getSingle(SomeInterface::class));
	}

	public function testGetSingleForNonAddedClass()
	{
		$this->expectException(NoValueException::class);

		$map = new ClassMultiMap(new ContainerBuilder());
		$map->getSingle(SomeClass::class);
	}

	public function testGetSingleForClassAddedTwice()
	{
		$this->expectException(MultipleValuesException::class);

		$map = new ClassMultiMap(new ContainerBuilder());

		$map->put(SomeClass::class, "someValue");
		$map->put(SomeClass::class, "someOtherValue");
		$map->getSingle(SomeClass::class);
	}

	public function testGetMultiForNonAddedClass()
	{
		$map = new ClassMultiMap(new ContainerBuilder());

		$this->assertSame([], $map->getMulti(SomeClass::class));
	}

	public function testGetMultiForClassAddedTwice()
	{
		$map = new ClassMultiMap(new ContainerBuilder());

		$map->put(SomeClass::class, "someValue");
		$map->put(SomeClass::class, "someOtherValue");
		$this->assertSame(["someValue", "someOtherValue"], $map->getMulti(SomeClass::class));
	}

}
