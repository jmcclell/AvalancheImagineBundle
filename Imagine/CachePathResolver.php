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
    /** @var string */
    private $defaultFrontController;

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
        CoreAssetsHelper $assets = null,
        $defaultFrontController
    ) {
        $this->manager    = $manager;
        $this->params     = $params;
        $this->router     = $router;
        $this->context    = $context;
        $this->assets     = $assets;
        $this->sourceRoot = $sourceRoot;
        // In some cases this may be required to force cached image generation.
        // I.e. front controller is not handling any of assets URI.
        $this->defaultFrontController = $defaultFrontController;
    }

    /**
     * Get real path to file for given filter
     *
     * @param string $path
     * @param string $filter
     *
     * @return string|null
     */
    public function getRealPath($path, $filter)
    {
        $fullPath = $this->manager->getOption($filter, 'source_root', $this->sourceRoot) . $path;

        return $this->normalizeFilePath($fullPath);
    }

    /**
     * Get cached path to file for given filter
     *
     * @param string $path
     * @param string $filter
     * @param bool   $evaluate
     *
     * @return null|string
     */
    public function getCachedPath($path, $filter, $evaluate = false)
    {
        return $this->findCachedFile($this->findCachedUri($path, $filter, false), $evaluate);
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
        $uri    = $this->findCachedUri($path, $filter, $absolute);
        $cached = $this->findCachedFile($uri, true);

        if ($cached) {
            if (!$realPath = $this->getRealPath($path, $filter)) {
                unlink($cached);

                return null;
            }

            if (filemtime($realPath) > filemtime($cached)) {
                unlink($cached);
                $cached = null;
            }
        }

        !$cached && ($uri = $this->findCachedUri($path, $filter, $absolute, true));

        return $uri;
    }

    /** @internal */
    private function findCachedUri($path, $filter, $absolute, $generate = false)
    {
        $assetsHost = !$generate;

        $path = ltrim($path, '/');
        $name = '_imagine_' . $filter . $this->params->getRouteSuffix($assetsHost);
        $uri  = $this->params->generateUrl($name, ['path' => $path], $absolute);

        $prefix  = preg_quote($this->params->getCachePrefix($assetsHost), '#');
        if ($assetsHost) {
            $pattern = sprintf('#^((?:[a-z]+:)?//.*?)?(?:/\w+[.]php)?(/%s.*?)$#i', $prefix);
            if (preg_match($pattern, $uri, $m)) {
                $uri = $m[1] . $m[2];
            }
        } elseif (!empty($this->defaultFrontController)) {
            $pattern = sprintf('#^((?:[a-z]+:)?//.*?)?(/\w+[.]php)?(/%s.*?)$#i', $prefix);
            if (preg_match($pattern, $uri, $m)) {
                empty($m[2]) && ($uri = $m[1] . '/' . $this->defaultFrontController . $m[3]);
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

        return $this->normalizeFilePath($cached);
    }

    private function normalizeFilePath($path)
    {
        // Normalize path only if it's required (DO NOT do this for remote storage like S3)
        (false === strpos($path, '://')) && ($path = realpath($path));

        return $path && is_file($path) ? $path : null;
    }
}
