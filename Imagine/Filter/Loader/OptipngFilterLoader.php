<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\OptipngFilter;
use Imagine\Image\ImagineInterface;

class OptipngFilterLoader implements LoaderInterface
{
    /** @var ImagineInterface */
    protected $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    public function load(array $options = array())
    {
        return new OptipngFilter(
            $this->imagine,
            isset($options['bin']) ? $options['bin'] : null,
            isset($options['level']) ? $options['level'] : null
        );
    }
}
