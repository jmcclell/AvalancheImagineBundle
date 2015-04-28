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
    /** @var FilterManager */
    private $filterManager;
    /** @var Filesystem */
    private $filesystem;
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
     * @param int               $permissions
     * @param int               $defaultQuality
     */
    public function __construct(
        CachePathResolver $cachePathResolver,
        ImagineInterface $imagine,
        FilterManager $filterManager,
        Filesystem $filesystem,
        ParamResolver $params,
        $permissions,
        $defaultQuality
    ) {
        $this->cachePathResolver = $cachePathResolver;
        $this->params            = $params;
        $this->imagine           = $imagine;
        $this->filterManager     = $filterManager;
        $this->filesystem        = $filesystem;
        $this->permissions       = $permissions;
        $this->defaultQuality    = $defaultQuality;
    }

    /**
     * Forces image caching and returns path to cached image.
     *
     * @param string  $basePath Deprecated parameter
     * @param string  $path
     * @param string  $filter
     * @param boolean $force
     *
     * @return string|null
     *
     * @throws RuntimeException
     */
    public function cacheImage($basePath, $path, $filter, $force = false)
    {
        $path = '/' . ltrim($path, '/');

        // if cache path cannot be determined, return 404
        if (!$cachedPath = $this->cachePathResolver->getCachedPath($path, $filter)) {
            return null;
        }

        // if the file has already been cached, just return path
        if (!$force && is_file($cachedPath)) {
            return $cachedPath;
        }

        if (!$sourcePath = $this->cachePathResolver->getRealPath($path, $filter)) {
            return null;
        }

        $this->ensureDirectoryExists($cachedPath);

        try {
            $image = $this->imagine->open($sourcePath);
        } catch (RuntimeException $e) {
            try {
                // Make sure source path is an image
                new ImageFile($sourcePath, false);

                // Do not pollute the space (don't copy anything; symlink is just fine)
                $this->filesystem->symlink($sourcePath, $cachedPath);
            } catch (RuntimeException $e) {
                return null;
            } catch (IOException $e) {
                // In case we were not able to create symlink we should return source path.
                // This means we'll be back here, but at least we'll not be polluting space with useless copies.
                return $sourcePath;
            }

            return $cachedPath;
        }

        $options = [
            'quality' => $this->filterManager->getOption($filter, 'quality', $this->defaultQuality),
            'format'  => $this->filterManager->getOption($filter, 'format', null),
        ];

        // Important! optipng filter returns an instance of ImageAssetWrapper.
        $this->filterManager->getFilter($filter)->apply($image)->save($cachedPath, $options);

        $this->ensureFilePermissions($cachedPath);

        return $cachedPath;
    }

    private function findStreamContext($path, $filter)
    {
        if (false === $at = strpos($path, '://')) {
            return null;
        }

        $contextName = substr($path, 0, $at);
        $context     = $this->filterManager->getOption($filter, $contextName . '_context', null);

        return is_array($context) ? stream_context_create([$contextName => $context]) : null;
    }

    private function ensureDirectoryExists($path)
    {
        // Do not perform directory creation for stream wrappers.
        if (strpos($path, '://')) {
            return;
        }

        $dir = pathinfo($path, PATHINFO_DIRNAME);

        if (is_dir($dir)) {
            return;
        }

        try {
            $this->filesystem->mkdir($dir);
        } catch (IOException $e) {
            throw new RuntimeException(sprintf('Could not create directory %s', $dir), 0, $e);
        }
    }

    private function ensureFilePermissions($file)
    {
        // Do not perform directory creation for stream wrappers.
        if (strpos($file, '://')) {
            return;
        }

        try {
            $this->filesystem->chmod($file, $this->permissions);
        } catch (IOException $e) {
            try {
                $this->filesystem->remove($file);
            } catch (IOException $ee) {
            }

            $message = sprintf('Could not set permissions %s on image saved in %s', $this->permissions, $file);
            throw new RuntimeException($message, 0, $e);
        }
    }
}
