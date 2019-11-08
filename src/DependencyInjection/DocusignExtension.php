<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use DocusignBundle\Adapter\AdapterDefinitionFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class DocusignExtension extends Extension
{
    public const STORAGE_NAME = 'docusign.storage';
    private const DEMO_API_URI = 'https://demo.docusign.net/restapi';
    private const DEMO_ACCOUNT_API_URI = 'https://account-d.docusign.com/oauth/token';
    private const ACCOUNT_API_URI = 'https://account.docusign.net/oauth/token';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('docusign.account_id', $config['account_id']);
        $container->setParameter('docusign.default_signer_name', $config['default_signer_name']);
        $container->setParameter('docusign.default_signer_email', $config['default_signer_email']);
        $container->setParameter('docusign.api_uri', $config['demo'] ? self::DEMO_API_URI : $config['api_uri']);
        $container->setParameter('docusign.account_api_uri', $config['demo'] ? self::DEMO_ACCOUNT_API_URI : self::ACCOUNT_API_URI);
        $container->setParameter('docusign.callback_route_name', $config['callback_route_name']);
        $container->setParameter('docusign.webhook_route_name', $config['webhook_route_name']);
        $container->setParameter('docusign.signatures_overridable', $config['signatures_overridable']);
        $container->setParameter('docusign.signatures', $config['signatures']);
        $container->setParameter('docusign.jwt.private_key', $config['jwt']['private_key']);
        $container->setParameter('docusign.jwt.integration_key', $config['jwt']['integration_key']);
        $container->setParameter('docusign.jwt.user_guid', $config['jwt']['user_guid']);

        if (!class_exists(FlysystemBundle::class)) {
            $this->flySystemCompatibility($container, $config);
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    /*
     * This method is here for the compatibility.
     */
    private function flySystemCompatibility(ContainerBuilder $container, array $config): void
    {
        $adapterFactory = new AdapterDefinitionFactory();
        $container
            ->registerForAutoconfiguration(PluginInterface::class)
            ->addTag('flysystem.plugin')
        ;
        foreach ($config['storages'] as $storageName => $storageConfig) {
            // Create adapter service definition
            if ($adapter = $adapterFactory->createDefinition($storageConfig['adapter'], $storageConfig['options'])) {
                // Native adapter
                $container->setDefinition('flysystem.adapter.'.$storageName, $adapter)->setPublic(false);
            } else {
                // Custom adapter
                $container->setAlias('flysystem.adapter.'.$storageName, $storageConfig['adapter'])->setPublic(false);
            }
            // Create storage service definition
            $definition = $this->createStorageDefinition(new Reference('flysystem.adapter.'.$storageName), $storageConfig);
            $container->setDefinition($storageName, $definition);
        }
    }

    /*
     * This method is here for the compatibility.
     */
    private function createStorageDefinition(Reference $adapter, array $config): Definition
    {
        $definition = new Definition(Filesystem::class);
        $definition->setPublic(false);
        $definition->setArgument(0, $adapter);
        $definition->setArgument(1, [
            'visibility' => $config['visibility'],
            'case_sensitive' => $config['case_sensitive'],
            'disable_asserts' => $config['disable_asserts'],
        ]);
        $definition->addTag('flysystem.storage');

        return $definition;
    }
}
