<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SkrzAutowiringExtensionTest extends PHPUnit_Framework_TestCase
{

	/** @var SkrzAutowiringExtension */
	private $skrzAutowiringExtension;

	/** @var ContainerBuilder */
	private $containerBuilder;

	protected function setUp()
	{
		$this->skrzAutowiringExtension = new SkrzAutowiringExtension;
		$this->containerBuilder = new ContainerBuilder;
	}

	public function testLoad()
	{
		$this->skrzAutowiringExtension->load(["autowiring" => [
		   "ignored_services" => [1],
			"preferred_services" => [2],
			"autoscan_psr4" => [3],
		]], $this->containerBuilder);

		$parameterBag = $this->containerBuilder->getParameterBag();
		$this->assertSame([1], $parameterBag->get("autowiring.ignored_services"));
		$this->assertSame([2], $parameterBag->get("autowiring.preferred_services"));
		$this->assertSame([3], $parameterBag->get("autowiring.autoscan_psr4"));
		$this->assertSame(
			["@Component", "@Controller", "@Repository", "@Service"],
			$parameterBag->get("autowiring.fast_annotation_checks")
		);
	}

}
