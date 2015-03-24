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

    /** {inheritdoc} */
    public function getMimeType()
    {
        $level = error_reporting(0);
        $type  = parent::getMimeType();
        error_reporting($level);

        if (false === $type) {
            if (false === strpos($this->getPathname(), '://')) {
                $error = error_get_last();
                throw new RuntimeException($error['message']);
            }

            // Make MIME assumption based on their extension for remote resources.
            if (preg_match('/[.](png|gif|jge?g|ico)$/', $this->getPathname(), $m)) {
                $type = $this->createMime($m[1]);
            }
        }

        return $type;
    }

    private function createMime($extension)
    {
        $map = [
            'jpg'  => 'jpeg',
            'jpeg' => 'jpeg',
            'gif'  => 'gif',
            'png'  => 'png',
            'ico'  => 'vnd.microsoft.icon',
        ];

        return isset($map[$extension]) ? sprintf('image/%s', $map[$extension]) : false;
    }
}
