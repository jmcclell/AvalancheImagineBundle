<?php

namespace Avalanche\Bundle\ImagineBundle\Routing;

use Avalanche\Bundle\ImagineBundle\Exception\UnsupportedOptionException;
use Avalanche\Bundle\ImagineBundle\Imagine\ParamResolver;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ImagineLoader extends Loader
{
    private $cacheParams;
    private $filters;

    public function __construct(ParamResolver $params, array $filters = [])
    {
        $this->cacheParams = $params;
        $this->filters     = $filters;
    }

    public function supports($resource, $type = null)
    {
        return $type === 'imagine';
    }

    public function load($resource, $type = null)
    {
        $routes = new RouteCollection();

        foreach ($this->filters as $filter => $options) {
            if (isset($options['path'])) {
                // FIXME: "path" option is not supported at the moment
                throw new UnsupportedOptionException('Unfortunately "path" option is not yet supported.');
                $this->addRoute($routes, $filter, '/' . trim($options['path'], '/') . '/{path}');

                continue;
            }

            foreach ($this->cacheParams->getRouteOptions() as $host => $cachePrefix) {
                $this->addRoute($routes, $filter, '/' . trim($cachePrefix, '/') . '/{filter}/{path}', $host);
            }
        }

        return $routes;
    }

    private function addRoute(RouteCollection $routes, $filter, $pattern, $host = '')
    {
        $requirements = ['_methods' => 'GET', 'filter' => '[A-z0-9_\-]*', 'path' => '.+'];
        $defaults     = ['_controller' => 'imagine.controller:filterAction'];
        $routeSuffix  = $host ? '_' . preg_replace('#[^a-z0-9]+#i', '_', $host) : '';

        $routes->add(
            '_imagine_' . $filter . $routeSuffix,
            new Route($pattern, array_merge($defaults, ['filter' => $filter]), $requirements, [], $host)
        );
    }
}
