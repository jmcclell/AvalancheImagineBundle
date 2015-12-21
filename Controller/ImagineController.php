<?php

namespace Avalanche\Bundle\ImagineBundle\Controller;

use Avalanche\Bundle\ImagineBundle\Imagine\CacheManager;
use Avalanche\Bundle\ImagineBundle\Imagine\ImageFile;
use Avalanche\Bundle\ImagineBundle\Imagine\Filter\FilterManager;
use DateTime;
use Exception;
use Imagine\Exception\RuntimeException;
use Imagine\Image\ImagineInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class ImagineController
{
    /** @var Request */
    private $request;

    /** @var ImagineInterface */
    private $imagine;

    /** @var CacheManager */
    private $cacheManager;

    /** @var FilterManager */
    private $filterManager;

    /** @var array */
    private $notFoundImages;

    public function __construct(ImagineInterface $imagine, CacheManager $cacheManager, FilterManager $filterManager)
    {
        $this->imagine       = $imagine;
        $this->cacheManager  = $cacheManager;
        $this->filterManager = $filterManager;
    }

    /**
     * This action applies a given filter to a given image, saves the image and
     * outputs it to the browser at the same time
     *
     * @param string $path
     * @param string $filter
     *
     * @return Response
     *
     * @throws Exception
     */
    public function filterAction($path, $filter)
    {
        $baseUrl = $this->request->getBaseUrl();

        try {
            try {
                $cachedPath = $this->cacheManager->cacheImage($baseUrl, $path, $filter);
            } catch (RuntimeException $e) {
                if (!isset($this->notFoundImages[$filter])) {
                    throw $e;
                }

                $path       = $this->notFoundImages[$filter];
                $cachedPath = $this->cacheManager->cacheImage($baseUrl, $path, $filter);
            }
        } catch (RouteNotFoundException $e) {
            throw new NotFoundHttpException('Filter doesn\'t exist.');
        }

        // if cache path cannot be determined, return 404
        if (null === $cachedPath) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        }

        try {
            // Using File instead of Imagine::open(), because i.e. image/x-icon is not widely supported.
            $file = new ImageFile($cachedPath, false);

            // TODO: add more media headers
            $headers  = ['content-type' => $file->getMimeType(), 'content-length' => $file->getSize()];
            $response = new Response($file->getContents(), 201, $headers);

            // Cache
            if (!$cacheType = $this->filterManager->getOption($filter, 'cache_type', false)) {
                return $response;
            }

            ($cacheType === 'public') ? $response->setPublic() : $response->setPrivate();

            $cacheExpires   = $this->filterManager->getOption($filter, 'cache_expires', '1 day');
            $expirationDate = new DateTime('+' . $cacheExpires);
            $maxAge         = $expirationDate->format('U') - time();

            if ($maxAge < 0) {
                throw new InvalidArgumentException('Invalid cache expiration date');
            }

            $response->setExpires($expirationDate);
            $response->setMaxAge($maxAge);

            return $response;
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * Set the request
     *
     * @param Request $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Set the notFoundImage
     *
     * @param array $notFoundImages
     */
    public function setNotFoundImages(array $notFoundImages)
    {
        $this->notFoundImages = $notFoundImages;
    }
}
