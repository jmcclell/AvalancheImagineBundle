<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use Imagine\Image\ImagineInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

class CacheManager
{
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
     */
    public function __construct(
        CachePathResolver $cachePathResolver,
        ImagineInterface $imagine,
        FilterManager $filterManager,
        Filesystem $filesystem,
        ParamResolver $params,
        $sourceRoot,
        $permissions
    ) {
        $this->cachePathResolver = $cachePathResolver;
        $this->params            = $params;
        $this->imagine           = $imagine;
        $this->filterManager     = $filterManager;
        $this->filesystem        = $filesystem;
        $this->sourceRoot        = $sourceRoot;
        $this->permissions       = $permissions;
    }

    /**
     * Forces image caching and returns path to cached image.
     *
     * @param string $basePath
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

        $browserPath = urldecode($this->cachePathResolver->getBrowserPath($path, $filter));

        (0 === strpos($browserPath, '//')) && ($browserPath = 'scheme:' . $browserPath);
        strpos($browserPath, '://') && ($browserPath = parse_url($browserPath, PHP_URL_PATH));

        if (!empty($basePath) && 0 === strpos($browserPath, $basePath)) {
            $browserPath = substr($browserPath, strlen($basePath));
        }

        // if cache path cannot be determined, return 404
        if (null === $browserPath) {
            return null;
        }

        $realPath = $this->params->getWebRoot() . $browserPath;

        $sourcePathRoot = $this->filterManager->getOption($filter, 'source_root', $this->sourceRoot);
        $sourcePath     = $sourcePathRoot . $path;

        // if the file has already been cached, just return path
        if (is_file($realPath)) {
            return $realPath;
        }

        if (!is_file($sourcePath)) {
            return null;
        }

        $dir = pathinfo($realPath, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            try {
                $this->filesystem->mkdir($dir);
            } catch (IOException $e) {
                throw new RuntimeException(sprintf('Could not create directory %s', $dir), 0, $e);
            }
        }

        // TODO: get rid of hard-coded quality
        $options = [
            'quality' => $this->filterManager->getOption($filter, 'quality', 100),
            'format'  => $this->filterManager->getOption($filter, 'format', null),
        ];

        // Important! optipng filter returns an instance of ImageAssetWrapper.
        $this->filterManager->getFilter($filter)
            ->apply($this->imagine->open($sourcePath))
            ->save($realPath, $options);

        try {
            $this->filesystem->chmod($realPath, $this->permissions);
        } catch (IOException $e) {
            try {
                $this->filesystem->remove($sourcePath);
            } catch (IOException $ee) {
            }

            $message = sprintf('Could not set permissions %s on image saved in %s', $this->permissions, $realPath);
            throw new RuntimeException($message, 0, $e);
        }

        return $realPath;
    }
}
