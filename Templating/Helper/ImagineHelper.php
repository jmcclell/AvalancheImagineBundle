<?php

namespace Avalanche\Bundle\ImagineBundle\Templating\Helper;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Templating\Helper\Helper;

class ImagineHelper extends Helper
{
    /** @var ContainerInterface */
    private $container;
    private $cachePathResolver;
    /** @var boolean */
    private $onTheFly;
    /** @var string[] */
    private $notFoundImages;

    public function __construct(ContainerInterface $container, $onTheFly = true, array $notFoundImages = [])
    {
        $this->container      = $container;
        $this->onTheFly       = $onTheFly;
        $this->notFoundImages = $notFoundImages;
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
        $uri = $this->useController()
            ? $this->getCachePathResolver()->getBrowserPath($path, $filter, $absolute)
            : $this->getCachePathResolver()->getCachedUri($path, $filter, $absolute);

        return $uri ? : $this->findNotFound($filter);
    }

    /** @return \Avalanche\Bundle\ImagineBundle\Imagine\CachePathResolver */
    protected function getCachePathResolver()
    {
        return $this->cachePathResolver
            ?: ($this->cachePathResolver = $this->container->get('imagine.cache.path.resolver'));
    }

    /** (non-PHPdoc) */
    public function getName()
    {
        return 'imagine';
    }

    protected function useController()
    {
        return $this->onTheFly;
    }

    protected function findNotFound($filter)
    {
        return isset($this->notFoundImages[$filter]) ? $this->notFoundImages[$filter] : null;
    }
}
