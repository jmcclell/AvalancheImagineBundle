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

    public function __construct(CachePathResolver $cachePathResolver)
    {
        $this->cachePathResolver = $cachePathResolver;
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
        return $this->cachePathResolver->getBrowserPath($path, $filter, $absolute);
    }

    /**
     * (non-PHPdoc)
     */
    public function getName()
    {
        return 'imagine';
    }
}
