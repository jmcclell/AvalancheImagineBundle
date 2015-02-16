<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

class CachePathResolver
{
    /** @var string */
    private $webRoot;

    /** @var string */
    private $cachePrefix;

    /** @var string */
    private $routeSuffix;

    /** @var RequestContext */
    private $context;

    /** @var RouterInterface */
    private $router;

    /** @var CoreAssetsHelper */
    private $assets;

    /**
     * Constructs cache path resolver with a given web root and cache prefix
     *
     * @param ParamResolver   $params
     * @param RouterInterface $router
     */
    public function __construct(
        ParamResolver $params,
        RouterInterface $router,
        RequestContext $context = null,
        CoreAssetsHelper $assets = null
    ) {
        $this->webRoot     = $params->getWebRoot();
        $this->cachePrefix = $this->preparePrefix($params, $assets);
        $this->routeSuffix = $this->prepareSuffix($params, $assets);
        $this->router      = $router;
        $this->context     = $context;
        $this->assets      = $assets;
    }

    /** @internal */
    private function preparePrefix(ParamResolver $params, CoreAssetsHelper $assets = null)
    {
        if ($assets) {
            $assetsHost = parse_url($assets->getUrl(''), PHP_URL_HOST);
            $options    = $params->getRouteOptions();

            return isset($options[$assetsHost]) ? $options[$assetsHost] : $options[''];
        }

        return $params->getCachePrefix();
    }

    /** @internal */
    private function prepareSuffix(ParamResolver $params, CoreAssetsHelper $assets = null)
    {
        if ($assets) {
            return '_' . preg_replace('#[^a-z0-9]+#i', '_', parse_url($assets->getUrl(''), PHP_URL_HOST));
        }

        return $params->getRouteSuffix();
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
        $realPath = realpath($this->webRoot . $path);

        $path = ltrim($path, '/');
        $uri  = $this->router->generate('_imagine_' . $filter . $this->routeSuffix, ['path' => $path], $absolute);
        $uri  = str_replace(urlencode($path), urldecode($path), $uri);

        // TODO: find better way then this hack.
        // This is required if we keep assets on separate [sub]domain or we use base non-root URL for them.
        if (preg_match('#^/\w+[.]php(/.*?)$#i', $uri, $m)) {
            $uri = $m[1];
        }

        $cached = realpath($this->webRoot . $uri);

        if (file_exists($cached) && !is_dir($cached) && filemtime($realPath) > filemtime($cached)) {
            unlink($cached);
        }

        return $uri;
    }
}
