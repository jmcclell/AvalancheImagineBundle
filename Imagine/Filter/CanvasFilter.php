<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter;

use Imagine\Filter\FilterInterface;
use Imagine\Image\BoxInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;
use Imagine\Image\Palette\Color\ColorInterface;
use Imagine\Image\Point;

/**
 * A canvas filter
 */
class CanvasFilter extends PasteFilter implements FilterInterface
{
    protected $imagine;

    protected $frame;

    protected $background;

    public function __construct(
        ImagineInterface $imagine,
        BoxInterface $frame,
        $x,
        $y,
        ColorInterface $background = null
    ) {
        $this->throwIfPointNotValid($x, 'x', array('left', 'right', 'center'));
        $this->throwIfPointNotValid($y, 'y', array('top', 'bottom', 'middle'));

        $this->imagine    = $imagine;
        $this->frame      = $frame;
        $this->background = $background;

        $this->x = $x;
        $this->y = $y;
    }

    /** {@inheritdoc} */
    public function apply(ImageInterface $image)
    {
        $canvas = $this->imagine->create($this->frame, $this->background);

        $x = is_string($this->x)
            ? $this->stringXtoInteger($this->x, $image, $canvas)
            : $this->x;
        $y = is_string($this->y)
            ? $this->stringYtoInteger($this->y, $image, $canvas)
            : $this->y;

        $canvas->paste($image, new Point($x, $y));

        return $canvas;
    }
}
