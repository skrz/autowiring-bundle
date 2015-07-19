<?php

namespace Skrz\Bundle\AutowiringBundle\Tests\DependencyInjection;

use PHPUnit_Framework_TestCase;
use Skrz\Bundle\AutowiringBundle\DependencyInjection\SkrzAutowiringExtension;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidTypeException;
use Symfony\Component\Config\Definition\Processor;

class SkrzAutowiringExtensionConfigTreeTest extends PHPUnit_Framework_TestCase
{
    /** @var SkrzAutowiringExtension */
    private $autowiringExtension;

    /** @var TreeBuilder */
    private $configTreeBuilder;

    /** @var Processor */
    private $processor;

    protected function setUp()
    {
        $this->autowiringExtension = new SkrzAutowiringExtension;
        $this->configTreeBuilder = $this->autowiringExtension->getConfigTreeBuilder();
        $this->processor = new Processor;
    }

    public function testConfigWithNoArray()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring']);
    }

    public function testConfigTreeBuilder()
    {
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => []]);
    }

    public function testIgnoredServicesWithNoArray()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'ignored_services' => ''
        ]]);
    }

    public function testPreferredServicesWithNoArray()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'preferred_services' => ''
        ]]);
    }

    public function testPreferredServices()
    {
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'preferred_services' => []
        ]]);
    }

    public function testFastAnnotationChecksWithNoArray()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'fast_annotation_checks' => ''
        ]]);
    }

    public function testFastAnnotationChecks()
    {
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'fast_annotation_checks' => []
        ]]);
    }

    public function testFastAnnotationChecksEnabledIncorrectType()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'fast_annotation_checks_enabled' => ''
        ]]);
    }

    public function testFastAnnotationChecksEnabled()
    {
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'fast_annotation_checks_enabled' => FALSE
        ]]);
    }

    public function testAutoscanPsr4IncorrectType()
    {
        $this->setExpectedException(InvalidTypeException::class);
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'autoscan_psr4' => ''
        ]]);
    }

    public function testAutoscanPsr4()
    {
        $this->processor->process($this->configTreeBuilder->buildTree(), ['autowiring' => [
            'autoscan_psr4' => []
        ]]);
    }

}
