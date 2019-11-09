<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use DocusignBundle\Adapter\AdapterDefinitionFactory;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

final class DocusignExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (!class_exists(FlysystemBundle::class)) {
            $container
                ->registerForAutoconfiguration(PluginInterface::class)
                ->addTag('flysystem.plugin');
        }
        $default = null;

        foreach ($config as $name => $value) {
            // Storage (FlySystem compatibility)
            if (\is_array($value['storage'])) {
                $value['storage'] = $this->flySystemCompatibility($container, $name, $value['storage']);
            }

            // Grant
            $container->register("docusign.grant.$name", JwtGrant::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$privateKey' => $value['auth_jwt']['private_key'],
                    '$integrationKey' => $value['auth_jwt']['integration_key'],
                    '$userGuid' => $value['auth_jwt']['user_guid'],
                    '$apiUri' => $value['demo'] ? JwtGrant::DEMO_API_URI : $value['api_uri'],
                    '$accountApiUri' => $value['demo'] ? JwtGrant::DEMO_ACCOUNT_API_URI : JwtGrant::ACCOUNT_API_URI,
                    '$ttl' => $value['auth_jwt']['ttl'],
                ]);

            // Envelope builder
            $container->register("docusign.envelope_builder.$name", EnvelopeBuilder::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$storage' => new Reference($value['storage']),
                    '$grant' => new Reference('grant'),
                    '$accountId' => $value['account_id'],
                    '$defaultSignerName' => $value['default_signer_name'],
                    '$defaultSignerEmail' => $value['default_signer_email'],
                    '$apiURI' => $value['api_uri'],
                    '$callbackRouteName' => $value['callback_route_name'],
                    '$webhookRouteName' => $value['webhook_route_name'],
                ]);

            // Signature extractor
            $container->register("docusign.signature_extractor.$name", SignatureExtractor::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$isOverridable' => $value['signatures_overridable'],
                    '$signatures' => $value['signatures'],
                ]);

            if (null === $default) {
                $container->setAlias("docusign.envelope_builder.$name", new Alias(EnvelopeBuilder::class));
                $container->setAlias("docusign.signature_extractor.$name", new Alias(SignatureExtractor::class));
                $container->setAlias("docusign.grant.$name", new Alias(JwtGrant::class));
                $container->setAlias("docusign.grant.$name", new Alias(GrantInterface::class));
                $default = $name;
            }
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('controllers.xml');
    }

    /*
     * This method is here for the FlySystem compatibility.
     */
    private function flySystemCompatibility(ContainerBuilder $container, string $name, array $config): string
    {
        $adapterFactory = new AdapterDefinitionFactory();
        $storageId = "flysystem.storage.$name";
        $adapterId = "flysystem.adapter.$name";

        // Create adapter service definition
        if ($adapter = $adapterFactory->createDefinition($config['adapter'], $config['options'])) {
            // Native adapter
            $container->setDefinition($adapterId, $adapter)->setPublic(false);
        } else {
            // Custom adapter
            $container->setAlias($adapterId, $config['adapter'])->setPublic(false);
        }
        // Create storage service definition
        $definition = $this->createStorageDefinition(new Reference($adapterId), $config);

        $container->setDefinition($storageId, $definition);

        return $storageId;
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
