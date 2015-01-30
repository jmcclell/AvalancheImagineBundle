<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\PasteFilter;
use Imagine\Image\ImagineInterface;
use InvalidArgumentException;

class PasteFilterLoader implements LoaderInterface
{
    /**
     * @var ImagineInterface
     */
    protected $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    /**
     * {@inheritDoc}
     *
     * @throws InvalidArgumentException
     */
    function load(array $options = array())
    {
        if (!isset($options['image'])) {
            throw new InvalidArgumentException('Option "image" is required.');
        }

        if (!is_readable($options['image'])) {
            throw new InvalidArgumentException('Expected image file exists and readable.');
        }

        $x = isset($options['x']) ? $options['x'] : 0;
        $y = isset($options['y']) ? $options['y'] : 0;

        $image = $this->imagine->open($options['image']);

        return new PasteFilter($image, $x, $y);
    }
}
