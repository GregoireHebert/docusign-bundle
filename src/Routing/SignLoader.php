<?php

declare(strict_types=1);

namespace DocusignBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class SignLoader extends Loader
{
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function load($resource, $type = null): RouteCollection
    {
        $routeCollection = new RouteCollection();

        // Load static routes: callback & webhook
        $routeCollection->add('docusign_callback', (new Route('docusign/callback', [
            '_controller' => 'docusign_callback',
        ]))->setMethods('GET'));
        $routeCollection->add('docusign_webhook', (new Route('docusign/webhook', [
            '_controller' => 'docusign_webhook',
        ]))->setMethods('POST'));

        // Load dynamic routes: sign per document
        foreach ($this->config as $name => $config) {
            $routeCollection->add("docusign_sign_$name", (new Route($config['sign_path'], [
                '_controller' => 'docusign_sign',
                '_docusign_name' => $name,
            ]))->setMethods('GET'));
        }

        return $routeCollection;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($resource, $type = null)
    {
        return 'docusign' === $type;
    }
}
