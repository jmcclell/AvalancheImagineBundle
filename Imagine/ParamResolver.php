<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Templating\Helper\CoreAssetsHelper;

class ParamResolver
{
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

    /**
     * Constructs cache path resolver with a given web root and cache prefix
     *
     * @param array          $hosts
     * @param RequestContext $context
     */
    public function __construct(array $hosts, RequestContext $context = null, CoreAssetsHelper $assets = null)
    {
        $this->context = $context;
        $this->hosts   = $hosts;
        $this->assets  = $assets;
    }

    /**
     * Get current best matching web root
     *
     * @return string
     */
    public function getWebRoot()
    {
        $this->prepare();

        return $this->webRoot;
    }

    /**
     * Get current best matching cache prefix
     *
     * @return string
     */
    public function getCachePrefix()
    {
        $this->prepare();

        return $this->cachePrefix;
    }

    /**
     * Get route's name suffix
     *
     * Since now host can be involved we need to distinguish imagine routes not only by used filter.
     *
     * @return string
     */
    public function getRouteSuffix()
    {
        $this->prepare();

        return $this->routeSuffix;
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

        if (!array_key_exists($this->context->getHost(), $options)) {
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

        $host = $this->context->getHost();
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
}
