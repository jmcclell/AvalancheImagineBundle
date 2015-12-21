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

        if (isset($config['hosts']) && (isset($config['cache_prefix']) || isset($config['web_root']))) {
            $message = 'You can only use "imagine.hosts" or ("imagine.cache_prefix" and "imagine.web_root"); not both';
            throw new InvalidArgumentException($message);
        }

        if (!isset($config['hosts'])) {
            $config['hosts'] = [
                'default' => [
                    'cache_prefix' => isset($config['cache_prefix']) ? $config['cache_prefix'] : 'media/cache',
                    'web_root'     => isset($config['web_root']) ? $config['web_root'] : '%kernel.root_dir%/../web',
                ]
            ];
        }

        $config['hosts'] = $this->standardizeHosts($config['hosts']);

        if (!isset($config['default_front_controller'])) {
            $config['default_front_controller'] = '';
        }

        if (!isset($config['on_the_fly'])) {
            $config['on_the_fly'] = true;
        }

        if (!isset($config['not_found_images'])) {
            $config['not_found_images'] = [];
        } elseif(is_string($config['not_found_images'])) {
            $path = $config['not_found_images'];
            foreach ($config['filters'] as $name => $filter) {
                $config['not_found_images'][$name] = $path;
            }
        }

        $parameters = [
            'source_root', 'permissions', 'default_quality', 'filters', 'not_found_images', 'hosts',
            'default_front_controller', 'on_the_fly',
        ];
        foreach ($parameters as $key) {
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

    public function getAlias()
    {
        return 'avalanche_imagine';
    }

    private function standardizeHosts(array $hosts)
    {
        foreach ($hosts as $name => &$options) {
            // Remove slashes from both sides.
            $options['cache_prefix'] = trim($options['cache_prefix'], '/\\');
            // Remove slashes from the rhs.
            $options['web_root']     = rtrim($options['web_root'], '/\\');
        }
        unset($options);

        return $hosts;
    }
}
