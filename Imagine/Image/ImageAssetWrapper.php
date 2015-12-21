<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine\Image;

use Assetic\Asset\AssetInterface;

class ImageAssetWrapper
{
    protected $asset;

    public function __construct(AssetInterface $asset)
    {
        $this->asset = $asset;
    }

    /**
     * @param string $realPath Real path where asset content should be dumped.
     * @param array  $options  Options are kept only for copatibilty reasons with ManipulatorInterface::save() method.
     */
    public function save($realPath, array $options = [])
    {
        file_put_contents($realPath, $this->asset->getContent());
    }
}
