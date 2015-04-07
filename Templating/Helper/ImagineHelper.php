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
    /** @var string[] */
    private $notFoundImages;

    public function __construct(CachePathResolver $cachePathResolver, $onTheFly = true, array $notFoundImages = [])
    {
        $this->cachePathResolver = $cachePathResolver;
        $this->onTheFly          = $onTheFly;
        $this->notFoundImages    = $notFoundImages;
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
