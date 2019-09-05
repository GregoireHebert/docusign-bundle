<?php

declare(strict_types=1);

namespace DocusignBundle\Exception;

use Throwable;

/**
 * flysystem compatibility.
 */
class MissingPackageException extends \RuntimeException
{
    public function __construct($message = '', Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
