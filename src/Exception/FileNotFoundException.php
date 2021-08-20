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

final class FileNotFoundException extends \RuntimeException
{
    private $path;

    public function __construct($path, $code = 0, \Exception $previous = null)
    {
        $this->path = $path;

        parent::__construct('File not found at path: '.$this->getPath(), $code, $previous);
    }

    public function getPath()
    {
        return $this->path;
    }
}
