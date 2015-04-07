<?php

namespace Avalanche\Bundle\ImagineBundle\Templating;

use Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class ImagineExtension extends Twig_Extension
{
    /**
     * @var CachePathResolver
     */
    private $cachePathResolver;
    /** @var boolean */
    private $onTheFly;
    /** @var string[] */
    private $notFoundImages;

    public function __construct(CachePathResolver $cachePathResolver, $onTheFly = true, array $notFoundImages = [])
    {
        $this->cachePathResolver = $cachePathResolver;
        $this->onTheFly          = $onTheFly;
        $this->notFoundImages    = $notFoundImages;
    }

    /**
     * (non-PHPdoc)
     */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('apply_filter', [$this, 'applyFilter']),
        ];
    }

    /**
     * {non-PHPdoc}
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('filter', [$this, 'applyFilter']),
        ];
    }

    /**
     * Gets cache path of an image to be filtered
     *
     * @param string  $path
     * @param string  $filter
     * @param boolean $absolute
     *
     * @return string
     */
    public function applyFilter($path, $filter, $absolute = false)
    {
        $uri = $this->onTheFly
            ? $this->cachePathResolver->getBrowserPath($path, $filter, $absolute)
            : $this->cachePathResolver->getCachedUri($path, $filter, $absolute);

        return $uri ? : (isset($this->notFoundImages[$filter]) ? $this->notFoundImages[$filter] : null);
    }

    /**
     * (non-PHPdoc)
     */
    public function getName()
    {
        return 'imagine';
    }
}
