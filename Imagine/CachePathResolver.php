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
     * @param FilterManager    $manager
     * @param ParamResolver    $params
     * @param RouterInterface  $router
     * @param string           $sourceRoot
     * @param RequestContext   $context
     * @param CoreAssetsHelper $assets
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
     * @return string|null
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
    private function findCachedUri($path, $filter, $absolute, $generate = false)
    {
        $assetsHost = !$generate;

        $path = ltrim($path, '/');
        $name = '_imagine_' . $filter . $this->params->getRouteSuffix($assetsHost);
        $uri  = $this->router->generate($name, ['path' => $path], $absolute);

        $prefix  = preg_quote($this->params->getCachePrefix($assetsHost), '#');
        if ($assetsHost) {
            $pattern = sprintf('#^((?:[a-z]+:)?//.*?)?(?:/\w+[.]php)?(/%s.*?)$#i', $prefix);
            if (preg_match($pattern, $uri, $m)) {
                $uri = $m[1] . $m[2];
            }
        }

        return str_replace(urlencode($path), $path, $uri);
    }

    /** @internal */
    private function findCachedFile($uri, $evaluate)
    {
        // TODO: find better way then this hack.
        // This is required if we keep assets on separate [sub]domain or we use base non-root URL for them.
        $cachedPath = $uri;
        $prefix     = preg_quote($this->params->getCachePrefix(), '#');
        $pattern    = sprintf('#^((?:[a-z]+:)?//.*?)?(?:/\w+[.]php)?(/%s.*?)$#i', $prefix);
        if (preg_match($pattern, $uri, $m)) {
            $cachedPath = $m[2];
        }

        $cached = $this->params->getWebRoot() . $cachedPath;
        if (!$evaluate) {
            return $cached;
        }

        $cached = realpath($cached);

        return $cached && file_exists($cached) && !is_dir($cached) ? $cached : null;
    }
}
