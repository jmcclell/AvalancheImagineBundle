<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

class ParamResolver
{
    /** @var Router */
    private $router;
    /** @var RequestContext */
    private $context;
    /** @var array */
    private $hosts;
    /** @var boolean */
    private $compiled;
    /** @var CoreAssetsHelper */
    private $assets;

    /** @var string[] */
    private $cachePrefix = [];
    /** @var string[] */
    private $webRoot = [];
    /** @var string[] */
    private $routeSuffix = [];

    /** @var string */
    private $assetsHost;

    /**
     * Constructs cache path resolver with a given web root and cache prefix
     *
     * @param array            $hosts
     * @param Router           $router
     * @param RequestContext   $context
     * @param CoreAssetsHelper $assets
     */
    public function __construct(
        array $hosts,
        Router $router,
        RequestContext $context = null,
        CoreAssetsHelper $assets = null
    ) {
        $this->hosts   = $hosts;
        $this->router  = $router;
        $this->context = $context;
        $this->assets  = $assets;
    }

    /**
     * Get current best matching web root
     *
     * @param boolean $assetsHost
     *
     * @return string
     */
    public function getWebRoot($assetsHost = true)
    {
        $this->prepare();

        return $this->webRoot[$assetsHost ? $this->getAssetsHost() : $this->getHost()];
    }

    /**
     * Get current best matching cache prefix
     *
     * @param boolean $assetsHost
     *
     * @return string
     */
    public function getCachePrefix($assetsHost = true)
    {
        $this->prepare();

        return $this->cachePrefix[$assetsHost ? $this->getAssetsHost() : $this->getHost()];
    }

    /**
     * Get route's name suffix
     *
     * Since now host can be involved we need to distinguish imagine routes not only by used filter.
     *
     * @param boolean $assetsHost
     *
     * @return string
     */
    public function getRouteSuffix($assetsHost = true)
    {
        $this->prepare();

        return $this->routeSuffix[$assetsHost ? $this->getAssetsHost() : $this->getHost()];
    }

    /**
     * Get host->cache-prefix association array
     *
     * This host->cache-prefix association array will be used when building route collection.
     * Main purpose of this is a multi-domain setup where we're serving HTML content from one sub-domain
     * and serving no-cookie assets from different sub-domain.
     *
     * @return array
     */
    public function getRouteOptions()
    {
        $this->prepare();

        $options = [];

        foreach ($this->hosts as $host => $opts) {
            $options[$host] = $opts['cache_prefix'];
        }
        unset($options['default']);

        if (!array_key_exists($this->getAssetsHost(), $options)) {
            $options[''] = $this->cachePrefix;
        }

        return $options;
    }

    /** @internal */
    private function prepare()
    {
        if ($this->compiled) {
            return;
        }

        $this->compiled = true;

        $this->compileFor($this->getHost());
        if ($this->getAssetsHost() !== $this->getHost()) {
            $this->compileFor($this->getAssetsHost());
        }
    }

    private function compileFor($host)
    {
        $map  = array(
            'cachePrefix' => 'cache_prefix',
            'webRoot'     => 'web_root',
        );

        foreach ($map as $field => $key) {
            if (isset($this->hosts[$host][$key])) {
                $this->routeSuffix[$host] = '_' . preg_replace('#[^a-z0-9]+#i', '_', $host);
                $this->{$field}[$host]    = $this->hosts[$host][$key];
            } elseif (isset($this->hosts['default'][$key])) {
                $this->{$field}[$host] = $this->hosts['default'][$key];
            } else {
                $message = '%s parameter is required by AvalancheImagineBundle; define imagine.hosts.default.%s';
                throw new \InvalidArgumentException(sprintf($message, $key, $key));
            }
        }
    }

    /**
     * Get current host name
     *
     * @return string
     */
    public function getHost()
    {
        return $this->context ? $this->context->getHost() : '';
    }

    /**
     * Get host name used by web assets
     *
     * In case we don't have access to web request return "" (empty string).
     *
     * @return string
     */
    public function getAssetsHost()
    {
        if ($this->assetsHost) {
            return $this->assetsHost;
        }

        if ($this->assets) {
            $host = parse_url($this->assets->getUrl(''), PHP_URL_HOST);
        } elseif ($this->context) {
            $host = $this->context->getHost();
        } else {
            $host = '';
        }

        return $this->assetsHost = $host;
    }

    // FIXME: shady way of dealing with remote resource
    public function generateUrl($name, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        $uri  = $this->router->generate($name, $parameters, $referenceType);
        $host = parse_url(0 === strpos($uri, '//') ? 's:' . $uri : $uri, PHP_URL_HOST);

        // Need to regenerate URI as we might end up with different ports set
        if (isset($this->hosts[$host]) && isset($this->hosts[$host]['ports'])) {
            $map       = ['http' => 'HttpPort', 'ssl' => 'HttpsPort'];
            $ports     = $this->hosts[$host]['ports'];
            $originals = [];
            foreach ($map as $key => $method) {
                $originals[$key] = $this->router->getContext()->{'get' . $method}();
                isset($ports[$key]) && $this->router->getContext()->{'set' . $method}($ports[$key]);
            }
            $uri = $this->router->generate($name, $parameters, $referenceType);
            foreach ($map as $key => $method) {
                $this->router->getContext()->{'set' . $method}($originals[$key]);
            }
        }

        return $uri;
    }
}
