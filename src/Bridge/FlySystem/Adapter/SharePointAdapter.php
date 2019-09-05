<?php

declare(strict_types=1);

namespace DocusignBundle\Bridge\FlySystem\Adapter;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use Office365\PHP\Client\Runtime\Auth\NetworkCredentialContext;
use Office365\PHP\Client\SharePoint\ClientContext;
use Office365\PHP\Client\SharePoint\File;

class SharePointAdapter implements AdapterInterface
{
    private $username;
    private $password;
    private $sharePointURL;
    private $sharePointHost;

    public function __construct(string $sharePointURL, string $username, string $password)
    {
        $this->sharePointURL = $sharePointURL;
        $this->username = $username;
        $this->password = $password;

        if (false !== $host = parse_url($sharePointURL)['host']) {
            $this->sharePointHost = $host;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config)
    {
        throw new \RuntimeException('method not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config)
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
    public function createDir($dirname, Config $config)
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
    public function has($path)
    {
        throw new \RuntimeException('method not implemented');
    }

    public function read($path)
    {
        $authCtx = new NetworkCredentialContext($this->username, $this->password);
        $authCtx->AuthType = CURLAUTH_NTLM;
        $ctx = new ClientContext($this->sharePointHost ?? $this->sharePointURL, $authCtx);

        return ['content' => File::openBinary($ctx, parse_url($this->sharePointURL)['path'])];
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
}
