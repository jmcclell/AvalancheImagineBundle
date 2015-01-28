<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter\Loader;

use Imagine\Filter\Basic\Crop;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class CropFilterLoader implements LoaderInterface
{
    public function load(array $options = array())
    {
        list($x, $y) = $options['start'];
        list($width, $height) = $options['size'];

        return new Crop(new Point($x, $y), new Box($width, $height));
    }
}
