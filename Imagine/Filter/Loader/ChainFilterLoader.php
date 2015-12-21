<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\ChainFilter;
use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Imagine\Exception\InvalidArgumentException;

class ChainFilterLoader implements LoaderInterface
{
    /**
     * @var FilterManager
     */
    protected $filterManager;

    public function __construct(FilterManager $filterManager)
    {
        $this->filterManager = $filterManager;
    }

    /**
     * {@inheritDoc}
     */
    function load(array $options = array())
    {
        if (!isset($options['filters']) || !is_array($options['filters'])) {
            throw new InvalidArgumentException('Expected filters key and type of array');
        }

        $filters = array();

        foreach ($options['filters'] as $loaderName => $opts) {
            $filters[] = $this->filterManager->getLoader($loaderName)
                ->load(is_array($opts) ? $opts : []);
        }

        return new ChainFilter($filters);
    }
}
