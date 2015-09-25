<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

class OptionalAutowiredClass
{

	public function __construct(SomeInterface $someClass, SomeInterface $someClass2)
	{
	}

}
