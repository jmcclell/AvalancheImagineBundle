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

        foreach ($this->filterManager->getFilterNames() as $filter) {
            $prefix = $this->filterManager->getOption($filter, 'source_root', $this->sourceRoot);
            (false === strpos($prefix, '://')) && ($prefix = realpath($prefix));

            if (0 !== strpos($file, $prefix)) {
                continue;
            }

            $paths[$filter] = $this->cacheManager->cacheImage('', substr($file, strlen($prefix)), $filter, $force);
        }

        return $paths;
    }
}