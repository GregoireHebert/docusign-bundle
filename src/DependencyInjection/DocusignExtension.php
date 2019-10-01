<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class DocusignExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('docusign.access_token', $config['access_token']);
        $container->setParameter('docusign.account_id', $config['account_id']);
        $container->setParameter('docusign.default_signer_name', $config['default_signer_name']);
        $container->setParameter('docusign.default_signer_email', $config['default_signer_email']);
        $container->setParameter('docusign.api_uri', $config['api_uri']);
        $container->setParameter('docusign.callback_route_name', $config['callback_route_name']);
        $container->setParameter('docusign.webhook_route_name', $config['webhook_route_name']);
        $container->setParameter('docusign.signatures_overridable', $config['signatures_overridable']);
        $container->setParameter('docusign.signatures', $config['signatures']);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
