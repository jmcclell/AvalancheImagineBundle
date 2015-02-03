<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Component\Routing\RouterInterface;

class CachePathResolver
{
    /**
     * @var string
     */
    private $webRoot;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * Constructs cache path resolver with a given web root and cache prefix
     *
     * @param string          $webRoot
     * @param RouterInterface $router
     */
    public function __construct($webRoot, RouterInterface $router)
    {
        $this->webRoot = $webRoot;
        $this->router  = $router;
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
        $uri  = $this->router->generate('_imagine_' . $filter, ['path' => $path], $absolute);
        $uri  = str_replace(urlencode($path), urldecode($path), $uri);

        $cached = realpath($this->webRoot . $uri);

        if (file_exists($cached) && !is_dir($cached) && filemtime($realPath) > filemtime($cached)) {
            unlink($cached);
        }

        return $uri;
    }
}
