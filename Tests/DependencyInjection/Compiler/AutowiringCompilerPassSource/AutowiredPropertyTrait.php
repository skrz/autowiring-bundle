<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo\Bar;

trait AutowiredPropertyTrait
{

	/**
	 * @var Bar
	 *
	 * @Autowired
	 */
	public $property;

}
