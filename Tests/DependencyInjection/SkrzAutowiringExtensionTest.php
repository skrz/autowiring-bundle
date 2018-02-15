<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SkrzAutowiringExtensionTest extends TestCase
{

	public function testLoad()
	{
		$containerBuilder = new ContainerBuilder();
		$extension = new SkrzAutowiringExtension();

		$extension->load(["autowiring" => [
			"ignored_services" => [1],
			"preferred_services" => [2],
		]], $containerBuilder);

		$parameterBag = $containerBuilder->getParameterBag();
		$this->assertSame([1], $parameterBag->get("autowiring.ignored_services"));
		$this->assertSame([2], $parameterBag->get("autowiring.preferred_services"));
		$this->assertSame(
			["@Component", "@Controller", "@Repository", "@Service"],
			$parameterBag->get("autowiring.fast_annotation_checks")
		);
	}

}
