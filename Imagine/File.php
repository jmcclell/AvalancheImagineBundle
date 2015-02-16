<?php

namespace Avalanche\Bundle\ImagineBundle\Imagine;

use Symfony\Component\HttpFoundation\File\File as BaseFile;

class File extends BaseFile
{
    /**
     * Returns the contents of the file.
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function getContents()
    {
        $level   = error_reporting(0);
        $content = file_get_contents($this->getPathname());
        error_reporting($level);

        if (false === $content) {
            $error = error_get_last();
            throw new \RuntimeException($error['message']);
        }

        return $content;
    }
}