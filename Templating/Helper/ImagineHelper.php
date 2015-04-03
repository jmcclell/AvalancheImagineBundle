<?php

namespace Avalanche\Bundle\ImagineBundle\Templating\Helper;

use Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver;
use Symfony\Component\Templating\Helper\Helper;

class ImagineHelper extends Helper
{
    /**
     * @var CachePathResolver
     */
    private $cachePathResolver;
    /** @var boolean */
    private $onTheFly;

    public function __construct(CachePathResolver $cachePathResolver, $onTheFly)
    {
        $this->cachePathResolver = $cachePathResolver;
        $this->onTheFly          = $onTheFly;
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
    public function filter($path, $filter, $absolute = false)
    {
        return $this->onTheFly
            ? $this->cachePathResolver->getBrowserPath($path, $filter, $absolute)
            : $this->cachePathResolver->getCachedUri($path, $filter, $absolute);
    }

    /**
     * (non-PHPdoc)
     */
    public function getName()
    {
        return 'imagine';
    }
}
