<?php
namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class SkrzAutowiringExtensionConfigTreeTest extends TestCase
{

	/** @var SkrzAutowiringExtension */
	private $extension;

	/** @var TreeBuilder */
	private $configTreeBuilder;

	/** @var Processor */
	private $processor;

	protected function setUp()
	{
		$this->extension = new SkrzAutowiringExtension();
		$this->configTreeBuilder = $this->extension->getConfigTreeBuilder();
		$this->processor = new Processor();
	}


	public function testRootInvalid()
	{
		$this->expectException(InvalidTypeException::class);
		$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => false]);
	}

	public function testRootNull()
	{
		$this->assertEquals(
			[
				"ignored_services" => [],
				"preferred_services" => [],
				"fast_annotation_checks" => [],
				"fast_annotation_checks_enabled" => true,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => null])
		);
	}

	public function testRootEmptyArray()
	{
		$this->assertEquals(
			[
				"ignored_services" => [],
				"preferred_services" => [],
				"fast_annotation_checks" => [],
				"fast_annotation_checks_enabled" => true,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => []])
		);
	}

	public function testIgnoredServicesInvalid()
	{
		$this->expectException(InvalidTypeException::class);
		$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
			"ignored_services" => "",
		]]);
	}

	public function testIgnoredServices()
	{
		$this->assertEquals(
			[
				"ignored_services" => ["foo"],
				"preferred_services" => [],
				"fast_annotation_checks" => [],
				"fast_annotation_checks_enabled" => true,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
				"ignored_services" => ["foo"]
			]])
		);
	}

	public function testPreferredServicesInvalid()
	{
		$this->expectException(InvalidTypeException::class);
		$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
			"preferred_services" => "",
		]]);
	}

	public function testPreferredServices()
	{
		$this->assertEquals(
			[
				"ignored_services" => [],
				"preferred_services" => ["bar"],
				"fast_annotation_checks" => [],
				"fast_annotation_checks_enabled" => true,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
				"preferred_services" => ["bar"]
			]])
		);
	}

	public function testFastAnnotationChecksInvalid()
	{
		$this->expectException(InvalidTypeException::class);
		$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
			"fast_annotation_checks" => "",
		]]);
	}

	public function testFastAnnotationChecks()
	{
		$this->assertEquals(
			[
				"ignored_services" => [],
				"preferred_services" => [],
				"fast_annotation_checks" => ["@Foo", "@Bar"],
				"fast_annotation_checks_enabled" => true,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
				"fast_annotation_checks" => ["@Foo", "@Bar"],
			]])
		);
	}

	public function testFastAnnotationChecksEnabledInvalid()
	{
		$this->expectException(InvalidTypeException::class);
		$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
			"fast_annotation_checks_enabled" => "",
		]]);
	}

	public function testFastAnnotationChecksEnabled()
	{
		$this->assertEquals(
			[
				"ignored_services" => [],
				"preferred_services" => [],
				"fast_annotation_checks" => [],
				"fast_annotation_checks_enabled" => false,
			],
			$this->processor->process($this->configTreeBuilder->buildTree(), ["autowiring" => [
				"fast_annotation_checks_enabled" => false,
			]])
		);
	}

}
