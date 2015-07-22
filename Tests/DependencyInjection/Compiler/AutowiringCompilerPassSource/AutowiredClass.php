<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

class AutowiredClass
{

	public function __construct(SomeClass $someClass)
	{
	}

}
