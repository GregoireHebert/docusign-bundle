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
use League\Flysystem\Handler;
use League\Flysystem\PluginInterface;

/**
 * flysystem compatibility.
 */
abstract class AbstractFilesystemDecorator implements FilesystemInterface
{
    protected $decorated;

    /**
     * @param FilesystemOperator|LegacyFilesystemInterface $decorated
     */
    public function __construct($decorated)
    {
        $this->decorated = $decorated;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path): string
    {
        return $this->decorated->read($path);
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        return $this->decorated->readStream($path);
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return $this->decorated->has($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        return $this->decorated->getMetadata($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        return $this->decorated->getSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        return $this->decorated->getMimetype($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        return $this->decorated->getTimestamp($path);
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        return $this->decorated->getVisibility($path);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, array $config = [])
    {
        return $this->decorated->update($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, array $config = [])
    {
        return $this->decorated->updateStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        return $this->decorated->rename($path, $newpath);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        return $this->decorated->deleteDir($dirname);
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, array $config = [])
    {
        return $this->decorated->createDir($dirname, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function put($path, $contents, array $config = [])
    {
        return $this->decorated->put($path, $contents, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function putStream($path, $resource, array $config = [])
    {
        return $this->decorated->putStream($path, $resource, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function readAndDelete($path)
    {
        return $this->decorated->readAndDelete($path);
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, Handler $handler = null)
    {
        return $this->decorated->get($path, $handler);
    }

    /**
     * {@inheritdoc}
     */
    public function addPlugin(PluginInterface $plugin)
    {
        return $this->decorated->addPlugin($plugin);
    }

    /**
     * {@inheritdoc}
     */
    public function fileExists(string $location): bool
    {
        return $this->decorated->fileExists($location);
    }

    /**
     * {@inheritdoc}
     */
    public function lastModified(string $path): int
    {
        return $this->decorated->lastModified($path);
    }

    /**
     * {@inheritdoc}
     */
    public function fileSize(string $path): int
    {
        return $this->decorated->fileSize($path);
    }

    /**
     * {@inheritdoc}
     */
    public function mimeType(string $path): string
    {
        return $this->decorated->mimeType($path);
    }

    /**
     * {@inheritdoc}
     */
    public function visibility(string $path): string
    {
        return $this->decorated->visibility($path);
    }

    /**
     * {@inheritdoc}
     */
    public function createDirectory(string $location, array $config = []): void
    {
        $this->decorated->createDirectory($location, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDirectory(string $location): void
    {
        $this->decorated->deleteDirectory($location);
    }

    /**
     * {@inheritdoc}
     */
    public function move(string $source, string $destination, array $config = []): void
    {
        $this->decorated->move($source, $destination, $config);
    }
}
