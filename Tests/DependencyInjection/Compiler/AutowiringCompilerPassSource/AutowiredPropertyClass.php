<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;

class AutowiredPropertyClass
{

	/**
	 * @var SomeClass
	 *
	 * @Autowired
	 */
	public $property;

}
