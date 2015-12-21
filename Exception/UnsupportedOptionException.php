<?php

namespace Avalanche\Bundle\ImagineBundle\Exception;

use Imagine\Exception\Exception;
use InvalidArgumentException as BaseException;

class UnsupportedOptionException extends BaseException implements Exception
{
}