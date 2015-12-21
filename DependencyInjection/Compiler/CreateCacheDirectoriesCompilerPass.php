<?php

namespace Avalanche\Bundle\ImagineBundle\DependencyInjection\Compiler;

use RuntimeException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CreateCacheDirectoriesCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $hosts      = $container->getParameter('imagine.hosts');
        $filters    = $container->getParameter('imagine.filters');
        $filesystem = new Filesystem();

        $dirs = [];
        foreach ($filters as $filter => $options) {
            foreach ($hosts as $host => $params) {
                $webRoot     = $params['web_root'];
                $cachePrefix = $params['cache_prefix'];

                $dirs[] = isset($options['path'])
                    ? $webRoot . '/' . $options['path']
                    : $webRoot . '/' . $cachePrefix . '/' . $filter;
            }
        }
        $dirs = array_unique($dirs);

        try {
            $filesystem->mkdir($dirs);
        } catch (IOException $e) {
            $message = sprintf('Could not create one of image cache directories: "%s"', implode(', ', $dirs));
            throw new RuntimeException($message, 0, $e);
        }
    }
}
