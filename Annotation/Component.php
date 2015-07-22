<?php

namespace Skrz\Bundle\AutowiringBundle\Annotation;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 *
 * @Annotation
 */
class Component
{

	/** @var string service ID */
	public $name;

	/** @var string service environments */
	public $env;

}
