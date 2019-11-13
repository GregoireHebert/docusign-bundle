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

namespace DocusignBundle\DependencyInjection;

use DocusignBundle\Adapter\AdapterDefinitionFactory;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Routing\DocusignLoader;
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
            if (!isset($value['storage']['storage'])) {
                $value['storage']['storage'] = $this->flySystemCompatibility($container, $name, $value['storage']);
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
                    '$storage' => new Reference($value['storage']['storage']),
                    '$grant' => new Reference("docusign.grant.$name"),
                    '$accountId' => $value['account_id'],
                    '$defaultSignerName' => $value['default_signer_name'],
                    '$defaultSignerEmail' => $value['default_signer_email'],
                    '$apiUri' => $value['api_uri'],
                    '$callback' => $value['callback'],
                    '$webhookRouteName' => 'docusign_webhook',
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
                $container->setAlias(EnvelopeBuilder::class, new Alias("docusign.envelope_builder.$name"));
                $container->setAlias(SignatureExtractor::class, new Alias("docusign.signature_extractor.$name"));
                $container->setAlias(JwtGrant::class, new Alias("docusign.grant.$name"));
                $container->setAlias(GrantInterface::class, new Alias("docusign.grant.$name"));
                $default = $name;
            }
        }

        $container->register('docusign.route_loader', DocusignLoader::class)
            ->setArgument('$config', $config)
            ->setPublic(false);

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
            $container->setAlias($adapterId, new Alias($config['adapter']))->setPublic(false);
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
