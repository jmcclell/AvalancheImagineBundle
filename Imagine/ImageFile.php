<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Imagine\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\File\File;

class ImageFile extends File
{
    /**
     * @param string $path
     * @param bool   $checkPath
     *
     * @throws RuntimeException
     */
    public function __construct($path, $checkPath = true)
    {
        parent::__construct($path, $checkPath);

        if (0 !== strpos($this->getMimeType(), 'image/')) {
            $message = sprintf('File %s is not an image; Avalanche operates only on images', $path);
            throw new RuntimeException($message);
        }
    }

    /**
     * Returns the contents of the file.
     *
     * @return string
     *
     * @throws RuntimeException
     */
    public function getContents()
    {
        $level   = error_reporting(0);
        $content = file_get_contents($this->getPathname());
        error_reporting($level);

        if (false === $content) {
            $error = error_get_last();
            throw new RuntimeException($error['message']);
        }

        return $content;
    }
}