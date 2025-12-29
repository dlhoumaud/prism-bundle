<?php

declare(strict_types=1);

namespace Prism\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration du bundle Prism
 *
 * Définit les options configurables du bundle
 */
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('prism');
        $rootNode = $treeBuilder->getRootNode();

        if (!$rootNode instanceof ArrayNodeDefinition) {
            throw new \RuntimeException('Root node must be an ArrayNodeDefinition');
        }

        /** @var \Symfony\Component\Config\Definition\Builder\NodeBuilder $children */
        $children = $rootNode->children();
        $enabled = $children->booleanNode('enabled');
        $enabled->defaultValue('%kernel.debug%');
        $enabled->info('Active ou désactive le bundle (auto-désactivé en production par défaut)');
        $enabled->end();

        $yamlPath = $children->scalarNode('yaml_path');
        $yamlPath->defaultValue('%kernel.project_dir%/prism/yaml');
        $yamlPath->info('Chemin vers le dossier contenant les scénarios YAML');
        $yamlPath->end();

        $scriptsPath = $children->scalarNode('scripts_path');
        $scriptsPath->defaultValue('%kernel.project_dir%/prism/scripts');
        $scriptsPath->info('Chemin vers le dossier contenant les scénarios PHP');
        $scriptsPath->end();

        $children->end();

        return $treeBuilder;
    }
}
