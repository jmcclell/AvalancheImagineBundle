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

    public function reloadFor($file, $force = false)
    {
        $paths = [];

        stream_is_local($file) && ($file = realpath($file));

        foreach ($this->filterManager->getFilterNames() as $filter) {
            $prefix = $this->filterManager->getOption($filter, 'source_root', $this->sourceRoot);
            stream_is_local($prefix) && ($prefix = realpath($prefix));

            if (0 !== strpos($file, $prefix)) {
                continue;
            }

            $source = substr($file, strlen($prefix));

            $paths[$filter] = $this->cacheManager->cacheImage('', $source, $filter, $force);
        }

        return $paths;
    }
}
