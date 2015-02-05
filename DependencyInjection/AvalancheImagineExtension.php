<?php

namespace Avalanche\Bundle\ImagineBundle\DependencyInjection;

use InvalidArgumentException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class AvalancheImagineExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('imagine.xml');

        $config = $this->mergeConfig($configs);

        $driver = isset($config['driver']) ? strtolower($config['driver']) : 'gd';

        if (!in_array($driver, array('gd', 'imagick', 'gmagick'))) {
            throw new InvalidArgumentException('Invalid imagine driver specified');
        }

        $container->setAlias('imagine', new Alias('imagine.' . $driver));

        foreach (array('cache_prefix', 'web_root', 'source_root', 'filters', 'not_found_images') as $key) {
            isset($config[$key]) && $container->setParameter('imagine.' . $key, $config[$key]);
        }
    }

    private function mergeConfig(array $configs)
    {
        $config = array();

        foreach ($configs as $cnf) {
            $config = array_merge_recursive($config, $cnf);
        }

        return $config;
    }

    function getAlias()
    {
        return 'avalanche_imagine';
    }
}
