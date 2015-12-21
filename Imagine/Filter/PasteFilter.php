<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter;

use Imagine\Exception\InvalidArgumentException;
use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;

class PasteFilter implements FilterInterface
{
    /**
     * @var ImageInterface
     */
    protected $pasteImage;

    /**
     * @var string|integer
     */
    protected $x;

    /**
     * @var string|integer
     */
    protected $y;

    public function __construct(ImageInterface $pasteImage, $x, $y)
    {
        $this->throwIfPointNotValid($x, 'x', array('left', 'right', 'center'));
        $this->throwIfPointNotValid($y, 'y', array('top', 'bottom', 'middle'));

        $this->pasteImage = $pasteImage;
        $this->x          = $x;
        $this->y          = $y;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(ImageInterface $image)
    {
        $x = is_string($this->x)
            ? $this->stringXtoInteger($this->x, $this->pasteImage, $image)
            : $this->x;

        $y = is_string($this->y)
            ? $this->stringYtoInteger($this->y, $this->pasteImage, $image)
            : $this->y;

        return $image->paste($this->pasteImage, new Point($x, $y));
    }

    /**
     * @param string         $point
     * @param ImageInterface $pasteImage
     * @param ImageInterface $image
     *
     * @return integer
     */
    protected function stringXtoInteger($point, ImageInterface $pasteImage, ImageInterface $image)
    {
        switch ($point) {
            case 'right':
                return (int) $image->getSize()->getWidth() - $pasteImage->getSize()->getWidth();
            case 'center':
                return (int) round(($image->getSize()->getWidth() / 2) - ($pasteImage->getSize()->getWidth() / 2));
            case 'left':
            default:
                return 0;
        }
    }

    /**
     * @param string         $point
     * @param ImageInterface $pasteImage
     * @param ImageInterface $image
     *
     * @return integer
     */
    protected function stringYtoInteger($point, ImageInterface $pasteImage, ImageInterface $image)
    {
        switch ($point) {
            case 'bottom':
                return (int) $image->getSize()->getHeight() - $pasteImage->getSize()->getHeight();
            case 'middle':
                return (int) round(($image->getSize()->getHeight() / 2) - ($pasteImage->getSize()->getHeight() / 2));
            case 'top':
            default:
                return 0;
        }
    }

    /**
     * @param integer|string $point
     * @param string         $pointName
     * @param array          $allowedLiterals
     *
     * @throws InvalidArgumentException
     */
    protected function throwIfPointNotValid($point, $pointName, array $allowedLiterals)
    {
        if (is_string($point) && in_array($point, $allowedLiterals)) {
            return;
        }

        if (is_integer($point) && $point >= 0) {
            return;
        }

        $message = 'Expected "%s" one of the [%s] or integer greater than zero';
        throw new InvalidArgumentException(sprintf($message, $pointName, implode('|', $allowedLiterals)));
    }
}
