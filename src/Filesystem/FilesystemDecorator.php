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

use League\Flysystem\DirectoryListing;
use League\Flysystem\FilesystemInterface;

/*
 * flysystem compatibility.
 */
if (interface_exists(FilesystemInterface::class)) {
    class FilesystemDecorator extends AbstractFilesystemDecorator
    {
        /**
         * {@inheritdoc}
         */
        public function listContents($directory = '', $recursive = false)
        {
            return $this->decorated->listContents($directory, $recursive);
        }

        /**
         * {@inheritdoc}
         */
        public function write($path, $contents, array $config = []): void
        {
            $this->decorated->write($path, $contents, $config);
        }

        /**
         * {@inheritdoc}
         */
        public function writeStream($path, $resource, array $config = []): void
        {
            $this->decorated->writeStream($path, $resource, $config);
        }

        /**
         * {@inheritdoc}
         */
        public function copy($path, $newpath): void
        {
            $this->decorated->copy($path, $newpath);
        }

        /**
         * {@inheritdoc}
         */
        public function delete($path): void
        {
            $this->decorated->delete($path);
        }

        /**
         * {@inheritdoc}
         */
        public function setVisibility($path, $visibility): void
        {
            $this->decorated->setVisibility($path, $visibility);
        }
    }
} else {
    class FilesystemDecorator extends AbstractFilesystemDecorator
    {
        /**
         * {@inheritdoc}
         */
        public function listContents(string $location, bool $deep = self::LIST_SHALLOW): DirectoryListing
        {
            return $this->decorated->listContents($location, $deep);
        }

        /**
         * {@inheritdoc}
         */
        public function write(string $location, string $contents, array $config = []): void
        {
            $this->decorated->write($location, $contents, $config);
        }

        /**
         * {@inheritdoc}
         */
        public function writeStream(string $location, $contents, array $config = []): void
        {
            $this->decorated->writeStream($location, $config, $config);
        }

        /**
         * {@inheritdoc}
         */
        public function copy(string $source, string $destination, array $config = []): void
        {
            $this->decorated->copy($source, $destination, $config);
        }

        /**
         * {@inheritdoc}
         */
        public function delete(string $location): void
        {
            $this->decorated->delete($location);
        }

        /**
         * {@inheritdoc}
         */
        public function setVisibility(string $path, string $visibility): void
        {
            $this->decorated->setVisibility($path, $visibility);
        }
    }
}
