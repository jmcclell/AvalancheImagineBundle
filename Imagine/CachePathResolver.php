<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

class CachePathResolver
{
    /** @var ParamResolver */
    private $params;
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
        $this->params  = $params;
        $this->router  = $router;
        $this->context = $context;
        $this->assets  = $assets;
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
        $realPath = realpath($this->params->getWebRoot(false) . $path);

        $path = ltrim($path, '/');
        $name = '_imagine_' . $filter . $this->params->getRouteSuffix();
        $uri  = $this->router->generate($name, ['path' => $path], $absolute);
        $uri  = str_replace(urlencode($path), urldecode($path), $uri);

        // TODO: find better way then this hack.
        // This is required if we keep assets on separate [sub]domain or we use base non-root URL for them.
        $prefix  = preg_quote($this->params->getCachePrefix(), '#');
        $pattern = sprintf('#^((?:[a-z]+:)?//.*?)?/\w+[.]php(/cache.*?)$#i', $prefix);
        if (preg_match($pattern, $uri, $m)) {
            $uri = $m[1] . $m[2];
        }

        $cached = realpath($this->params->getWebRoot(false) . $uri);

        if (file_exists($cached) && !is_dir($cached) && filemtime($realPath) > filemtime($cached)) {
            unlink($cached);
        }

        return $uri;
    }
}
