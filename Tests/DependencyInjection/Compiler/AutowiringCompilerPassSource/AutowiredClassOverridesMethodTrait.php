<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource;

use Skrz\Bundle\AutowiringBundle\Annotation\Autowired;
use Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection\Compiler\AutowiringCompilerPassSource\Foo2\Bar2;

class AutowiredClassOverridesMethodTrait
{

	use AutowiredMethodTrait;

	/**
	 * @param Bar2 $bar
	 *
	 * @Autowired
	 */
	public function setBar($bar)
	{
	}

}
