<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

class CachePathResolver
{
    /** @var FilterManager */
    private $manager;
    /** @var ParamResolver */
    private $params;
    /** @var RouterInterface */
    private $router;
    /** @var string */
    private $sourceRoot;
    /** @var RequestContext */
    private $context;
    /** @var CoreAssetsHelper */
    private $assets;

    /**
     * Constructs cache path resolver with a given web root and cache prefix
     *
     * @param ParamResolver   $params
     * @param RouterInterface $router
     */
    public function __construct(
        FilterManager $manager,
        ParamResolver $params,
        RouterInterface $router,
        $sourceRoot,
        RequestContext $context = null,
        CoreAssetsHelper $assets = null
    ) {
        $this->manager    = $manager;
        $this->params     = $params;
        $this->router     = $router;
        $this->context    = $context;
        $this->assets     = $assets;
        $this->sourceRoot = $sourceRoot;
    }

    /**
     * Gets filtered path for rendering in the browser
     *
     * @param string  $path
     * @param string  $filter
     * @param boolean $absolute
     *
     * @return mixed
     */
    public function getBrowserPath($path, $filter, $absolute = false)
    {
        $realPath = realpath($this->manager->getOption($filter, 'source_root', $this->sourceRoot) . $path);
        $uri      = $this->findCachedUri($path, $filter, $absolute);
        $cached   = $this->findCachedFile($uri);

        if (file_exists($cached) && !is_dir($cached) && filemtime($realPath) > filemtime($cached)) {
            unlink($cached);
        }

        return $uri;
    }

    /** @internal */
    private function findCachedUri($path, $filter, $absolute)
    {
        $path = ltrim($path, '/');
        $name = '_imagine_' . $filter . $this->params->getRouteSuffix();
        $uri  = $this->router->generate($name, ['path' => $path], $absolute);

        return str_replace(urlencode($path), urldecode($path), $uri);
    }

    /** @internal */
    private function findCachedFile($uri)
    {
        // TODO: find better way then this hack.
        // This is required if we keep assets on separate [sub]domain or we use base non-root URL for them.
        $cachedPath = $uri;
        $prefix     = preg_quote($this->params->getCachePrefix(), '#');
        $pattern    = sprintf('#^((?:[a-z]+:)?//.*?)?/\w+[.]php(/%s.*?)$#i', $prefix);
        if (preg_match($pattern, $uri, $m)) {
            $cachedPath = $m[1] . $m[2];
        }

        return realpath($this->params->getWebRoot() . $cachedPath);
    }
}
