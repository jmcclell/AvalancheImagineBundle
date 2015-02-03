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
        $final = null;
        foreach ($filters as $filter) {
            if ($final) {
                $message = sprintf('Instance of %s must be used as last filter only', $final);
                throw new InvalidArgumentException($message);
            }

            if (!$filter instanceof FilterInterface) {
                throw new InvalidArgumentException('Instance of Imagine\\Filter\\FilterInterface expected');
            }

            if ($filter instanceof FinalFilterInterface) {
                $final = get_class($filter);
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
