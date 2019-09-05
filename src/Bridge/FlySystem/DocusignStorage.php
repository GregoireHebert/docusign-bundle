<?php

declare(strict_types=1);

namespace DocusignBundle\Bridge\FlySystem;

use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\Handler;
use League\Flysystem\PluginInterface;

class DocusignStorage implements FilesystemInterface
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    public function read($path)
    {
        return $this->adapter->read($path)['content'];
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getVisibility($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function setVisibility($path, $visibility)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function put($path, $contents, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function putStream($path, $resource, array $config = [])
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function readAndDelete($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function get($path, Handler $handler = null)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function addPlugin(PluginInterface $plugin)
    {
        throw new \RuntimeException('method not implemented');
    }
}
