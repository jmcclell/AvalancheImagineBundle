<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Asset\StringAsset;
use Assetic\Exception\FilterException;
use Assetic\Filter\OptiPngFilter as CoreFilter;
use Avalanche\Bundle\ImagineBundle\Imagine\Image\ImageAssetWrapper;
use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\ImagineInterface;

class OptipngFilter implements FilterInterface, FinalFilterInterface
{
    /**
     * @var CoreFilter
     */
    protected $filter;

    /** @var ImagineInterface */
    protected $imagine;

    /**
     * @param string|null $bin
     * @param int|null    $level
     */
    public function __construct(ImagineInterface $imagine, $bin = null, $level = null)
    {
        $this->imagine = $imagine;

        $filter = $bin ? new CoreFilter($bin) : new CoreFilter();

        $level && $filter->setLevel($level);

        $this->filter = $filter;
    }

    /**
     * {@inheritDoc}
     *
     * @return AssetInterface|ImageInterface
     */
    public function apply(ImageInterface $image)
    {
        $asset = new StringAsset($image->get('png'));
        $asset->load();

        try {
            $this->filter->filterDump($asset);
        } catch (FilterException $e) {
            // FilterException is thrown when "optipng" utility was not found.
            // This should never happen on production environment; maybe we should enforce it?
        }

        // TODO: workaround hackish way of applying optipng and other compression filters.
        // Without that we're loosing some optimizations by running content through image library functions!
        return new ImageAssetWrapper($asset);
//        return $this->imagine->load($asset->getContent());
    }
}
