<?php

declare(strict_types=1);

namespace Prism\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * Extension du bundle Prism
 *
 * Charge la configuration des services et configure l'injection de dépendances
 */
class PrismExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Enregistrer la configuration comme paramètres
        $container->setParameter('prism.enabled', $config['enabled']);
        $container->setParameter('prism.yaml_path', $config['yaml_path']);
        $container->setParameter('prism.scripts_path', $config['scripts_path']);

        // Ne charger les services que si le bundle est activé
        if ($config['enabled']) {
            $loader = new YamlFileLoader(
                $container,
                new FileLocator(__DIR__ . '/../../config')
            );
            $loader->load('services.yaml');
        }
    }

    public function getAlias(): string
    {
        return 'prism';
    }
}
