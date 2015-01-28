<?php
namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter;

use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use InvalidArgumentException;

class ChainFilter implements FilterInterface
{
    /**
     * @var FilterInterface[]
     */
    protected $filters;

    /**
     * @param FilterInterface[] $filters
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $filters)
    {
        foreach ($filters as $filter) {
            if (false == ($filter instanceof FilterInterface)) {
                throw new InvalidArgumentException('Instance of Imagine\\Filter\\FilterInterface expected');
            }
        }

        $this->filters = $filters;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(ImageInterface $image)
    {
        foreach ($this->filters as $filter) {
            $image = $filter->apply($image);
        }

        return $image;
    }
}
