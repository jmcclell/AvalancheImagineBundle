<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\CanvasFilter;
use Imagine\Filter\Advanced\Canvas;
use Imagine\Filter\Basic\Thumbnail;
use Imagine\Image\Box;
use Imagine\Image\ImagineInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;

class CanvasFilterLoader implements LoaderInterface
{
    private $imagine;

    public function __construct(ImagineInterface $imagine)
    {
        $this->imagine = $imagine;
    }

    public function load(array $options = array())
    {
        list($w, $h) = $options['size'];
        list($x, $y) = (isset($options['placement']) ? $options['placement'] : [0, 0]);

        return new CanvasFilter($this->imagine, new Box($w, $h), $x, $y, null);
    }
}
