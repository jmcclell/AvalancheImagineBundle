<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;

class CacheReloader
{
    private $sourceRoot;
    private $cacheManager;
    private $filterManager;

    public function __construct($sourceRoot, CacheManager $cacheManager, FilterManager $filterManager)
    {
        $this->sourceRoot    = $sourceRoot;
        $this->cacheManager  = $cacheManager;
        $this->filterManager = $filterManager;
    }

    public function reloadFor($file, $force = false, $saveAs = null)
    {
        $paths = [];

        stream_is_local($file) && ($file = realpath($file));
        $saveAs && stream_is_local($saveAs) && ($saveAs = realpath(dirname($saveAs)) . '/' . basename($saveAs));

        foreach ($this->filterManager->getFilterNames() as $filter) {
            $prefix = $this->filterManager->getOption($filter, 'source_root', $this->sourceRoot);
            stream_is_local($prefix) && ($prefix = realpath($prefix));

            if (0 !== strpos($file, $prefix)) {
                continue;
            }

            if ($saveAs && 0 !== strpos($saveAs, $prefix)) {
                continue;
            }

            $source = substr($file, strlen($prefix));
            $target = $saveAs ? substr($saveAs, strlen($prefix)) : null;

            $paths[$filter] = $this->cacheManager->cacheImage('', $source, $filter, $force, $target);
        }

        return $paths;
    }

    public function cleanupFor($file)
    {
        $paths = [];

        stream_is_local($file) && ($file = realpath(dirname($file)) . '/' . basename($file));

        foreach ($this->filterManager->getFilterNames() as $filter) {
            $prefix = $this->filterManager->getOption($filter, 'source_root', $this->sourceRoot);
            stream_is_local($prefix) && ($prefix = realpath($prefix));

            if (0 !== strpos($file, $prefix)) {
                continue;
            }

            $this->cacheManager->removeCacheImage(substr($file, strlen($prefix)), $filter);
        }

        return $paths;
    }
}
