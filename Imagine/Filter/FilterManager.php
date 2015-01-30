<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use Imagine\Exception\InvalidArgumentException;

class FilterManager
{
    private $filters;
    private $loaders;
    private $services;

    public function __construct(array $filters = array())
    {
        $this->filters  = $filters;
        $this->loaders  = array();
        $this->services = array();
    }

    public function addLoader($name, LoaderInterface $loader)
    {
        $this->loaders[$name] = $loader;
    }

    /**
     * @param string $name
     *
     * @return LoaderInterface
     *
     * @throws InvalidArgumentException
     */
    public function getLoader($name)
    {
        if (!isset($this->loaders[$name])) {
            $message = sprintf('Could not find loader for "%s" filter type', $name);
            throw new InvalidArgumentException($message);
        }

        return $this->loaders[$name];
    }

    /**
     * @param string $filter
     *
     * @return \Imagine\Filter\FilterInterface
     *
     * @throws InvalidArgumentException
     */
    public function getFilter($filter)
    {
        if (!isset($this->filters[$filter])) {
            $message = sprintf('Could not find image filter "%s"', $filter);
            throw new InvalidArgumentException($message);
        }

        $options = $this->filters[$filter];

        if (!isset($options['type'])) {
            $message = sprintf('Filter type for "%s" image filter must be specified', $filter);
            throw new InvalidArgumentException($message);
        }

        if (!isset($options['options'])) {
            $message = sprintf('Options for filter type "%s" must be specified', $filter);
            throw new InvalidArgumentException($message);
        }

        return $this->getLoader($options['type'])->load($options['options']);
    }

    public function getOption($filter, $name, $default = null)
    {
        $options = $this->filters[$filter];

        return isset($options['options'][$name]) ? $options['options'][$name] : $default;
    }
}
