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

        foreach ($filters as $filter => $options) {
            if (isset($options['path'])) {
                $dir = $webRoot . '/' . $options['path'];
            } else {
                $dir = $webRoot . '/' . $cachePrefix . '/' . $filter;
            }

            try {
                $filesystem->mkdir($dir);
            } catch (IOException $e) {
                $message = sprintf('Could not create directory for caching processed images in "%s"', $dir);
                throw new RuntimeException($message, 0, $e);
            }
        }
    }
}
