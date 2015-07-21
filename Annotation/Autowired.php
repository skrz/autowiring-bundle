<?php

/**
 * This file is part of the AutowiringBundle.
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Skrz\Bundle\AutowiringBundle\Annotation;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 *
 * @Annotation
 */
final class Autowired
{

	/** @var string service ID */
	public $name;

}
