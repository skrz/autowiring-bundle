<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo2\Bar2;

class AutowiredClassOverridesPropertyTrait
{
	use AutowiredPropertyTrait;

	/**
	 * @var Bar2
	 *
	 * @Autowired
	 */
	public $property;
}
