<?php

namespace Skrz\Bundle\AutowiringBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

/**
 * @author Jakub Kulhan <jakub.kulhan@gmail.com>
 */
class SkrzAutowiringExtension extends Extension implements ConfigurationInterface
{

	/**
	 * {@inheritdoc}
	 */
	public function getConfigTreeBuilder()
	{
		$treeBuilder = new TreeBuilder();
		$rootNode = $treeBuilder->root("autowiring");

		$ignoredServicesNode = $rootNode->children()->arrayNode("ignored_services");
		$ignoredServicesNode->defaultValue([])->prototype("scalar");

		$preferredServicesNode = $rootNode->children()->arrayNode("preferred_services");
		$preferredServicesNode->defaultValue([])->prototype("scalar");

		$fastAnnotationChecksNode = $rootNode->children()->arrayNode("fast_annotation_checks");
		$fastAnnotationChecksNode->defaultValue([])->prototype("scalar");

		$fastAnnotationChecksEnabledNode = $rootNode->children()->booleanNode("fast_annotation_checks_enabled");
		$fastAnnotationChecksEnabledNode->defaultValue(true);

		$autoscanPsr4Node = $rootNode->children()->arrayNode("autoscan_psr4");
		$autoscanPsr4Node->defaultValue([])->prototype("scalar");

		return $treeBuilder;
	}

	/**
	 * {@inheritdoc}
	 */
	public function load(array $config, ContainerBuilder $container)
	{
		$autowiringConfig = $this->processConfiguration($this, $config);
		$container->setParameter("autowiring.ignored_services", $autowiringConfig["ignored_services"]);
		$container->setParameter("autowiring.preferred_services", $autowiringConfig["preferred_services"]);
		$container->setParameter("autowiring.autoscan_psr4", $autowiringConfig["autoscan_psr4"]);

		if ($autowiringConfig["fast_annotation_checks_enabled"]) {
			$container->setParameter("autowiring.fast_annotation_checks", array_merge([
				"@Component",
				"@Controller",
				"@Repository",
				"@Service",
			], $autowiringConfig["fast_annotation_checks"]));
		}
	}

}
