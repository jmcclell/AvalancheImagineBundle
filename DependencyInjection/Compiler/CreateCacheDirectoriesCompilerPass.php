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
        $webRoot     = $container->getParameter('imagine.web_root');
        $cachePrefix = $container->getParameter('imagine.cache_prefix');
        $filters     = $container->getParameter('imagine.filters');
        $filesystem  = new Filesystem();

        $dirs = [];
        foreach ($filters as $filter => $options) {
            $dirs[] = isset($options['path'])
                ? $webRoot . '/' . $options['path']
                : $webRoot . '/' . $cachePrefix . '/' . $filter;
        }

        try {
            $filesystem->mkdir($dirs);
        } catch (IOException $e) {
            $message = sprintf('Could not create one of image cache directories: "%s"', implode(', ', $dirs));
            throw new RuntimeException($message, 0, $e);
        }
    }
}
