<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace DocusignBundle\Exception;

/**
 * flysystem compatibility.
 */
class MissingPackageException extends \RuntimeException
{
    public function __construct($message = '', \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
