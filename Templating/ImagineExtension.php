<?php

namespace Avalanche\Bundle\ImagineBundle\Templating;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;

class ImagineExtension extends Twig_Extension
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

    /** (non-PHPdoc} */
    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('apply_filter', [$this, 'applyFilter']),
        ];
    }

    /** {non-PHPdoc} */
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
