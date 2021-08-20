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

namespace DocusignBundle\Filesystem;

use League\Flysystem\FilesystemInterface as LegacyFilesystemInterface;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\FilesystemReader;

/*
 * flysystem compatibility.
 */
if (interface_exists(FilesystemReader::class)) {
    interface FilesystemInterface extends FilesystemOperator
    {
    }
} else {
    interface FilesystemInterface extends LegacyFilesystemInterface
    {
    }
}
