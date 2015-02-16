<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Imagine\Image\ImagineInterface;
use Imagine\Exception\RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheManager
{
    /** @var CachePathResolver */
    private $cachePathResolver;
    /** @var ParamResolver */
    private $params;
    /** @var ImagineInterface */
    private $imagine;
    /** @var Filesystem */
    private $filesystem;
    /** @var string */
    private $sourceRoot;
    /** @var int */
    private $permissions;
    /** @var int */
    private $defaultQuality;

    /**
     * CacheManager constructor.
     *
     * @param CachePathResolver $cachePathResolver
     * @param ImagineInterface  $imagine
     * @param FilterManager     $filterManager
     * @param Filesystem        $filesystem
     * @param ParamResolver     $params
     * @param string            $sourceRoot
     * @param int               $permissions
     * @param int               $defaultQuality
     */
    public function __construct(
        CachePathResolver $cachePathResolver,
        ImagineInterface $imagine,
        FilterManager $filterManager,
        Filesystem $filesystem,
        ParamResolver $params,
        $sourceRoot,
        $permissions,
        $defaultQuality
    ) {
        $this->cachePathResolver = $cachePathResolver;
        $this->params            = $params;
        $this->imagine           = $imagine;
        $this->filterManager     = $filterManager;
        $this->filesystem        = $filesystem;
        $this->sourceRoot        = $sourceRoot;
        $this->permissions       = $permissions;
        $this->defaultQuality    = $defaultQuality;
    }

    /**
     * Forces image caching and returns path to cached image.
     *
     * @param string $basePath Deprecated parameter
     * @param string $path
     * @param string $filter
     *
     * @return string|null
     *
     * @throws RuntimeException
     */
    public function cacheImage($basePath, $path, $filter)
    {
        $path = '/' . ltrim($path, '/');

        // if cache path cannot be determined, return 404
        if (!$cachedPath = $this->cachePathResolver->getCachedPath($path, $filter)) {
            return null;
        }

        // if the file has already been cached, just return path
        if (is_file($cachedPath)) {
            return $cachedPath;
        }

        if (!is_file($sourcePath = $this->cachePathResolver->getRealPath($path, $filter))) {
            return null;
        }

        $dir = pathinfo($cachedPath, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            try {
                $this->filesystem->mkdir($dir);
            } catch (IOException $e) {
                throw new RuntimeException(sprintf('Could not create directory %s', $dir), 0, $e);
            }
        }

        $options = [
            'quality' => $this->filterManager->getOption($filter, 'quality', $this->defaultQuality),
            'format'  => $this->filterManager->getOption($filter, 'format', null),
        ];

        // Important! optipng filter returns an instance of ImageAssetWrapper.
        $this->filterManager->getFilter($filter)->apply($image)->save($cachedPath, $options);

        try {
            $this->filesystem->chmod($cachedPath, $this->permissions);
        } catch (IOException $e) {
            try {
                $this->filesystem->remove($cachedPath);
            } catch (IOException $ee) {
            }

            $message = sprintf('Could not set permissions %s on image saved in %s', $this->permissions, $cachedPath);
            throw new RuntimeException($message, 0, $e);
        }

        return $cachedPath;
    }
}
