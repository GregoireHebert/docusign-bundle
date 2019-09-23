<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use DocusignBundle\Adapter\AdapterDefinitionFactory;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class DocusignExtension extends Extension
{
    public const STORAGE_NAME = 'docusign.storage';

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $config = $processor->processConfiguration($configuration, $configs);

        $this->registerConfiguration($container, $config);
        $this->flySystemCompatibility($container, $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    private function registerConfiguration(ContainerBuilder $container, array $config): void
    {
        $container->setParameter('docusign.accessToken', $config['accessToken']);
        $container->setParameter('docusign.accountId', $config['accountId']);
        $container->setParameter('docusign.defaultSignerName', $config['defaultSignerName']);
        $container->setParameter('docusign.defaultSignerEmail', $config['defaultSignerEmail']);
        $container->setParameter('docusign.apiURI', $config['apiURI']);
        $container->setParameter('docusign.callBackRouteName', $config['callbackRouteName']);
        $container->setParameter('docusign.webHookRouteName', $config['webHookRouteName']);
        $container->setParameter('docusign.signatures_overridable', $config['signatures_overridable']);
        $container->setParameter('docusign.signatures', $config['signatures']);
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
