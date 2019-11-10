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
use DocusignBundle\Controller\Consent;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\CreateDocument;
use DocusignBundle\EnvelopeCreator\CreateRecipient;
use DocusignBundle\EnvelopeCreator\DefineEnvelope;
use DocusignBundle\EnvelopeCreator\EnvelopeBuilderCallableInterface;
use DocusignBundle\EnvelopeCreator\EnvelopeCreator;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Routing\DocusignLoader;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
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

        $container
            ->registerForAutoconfiguration(EnvelopeBuilderCallableInterface::class)
            ->addTag('docusign.envelope_builder.action');

        $container
            ->registerForAutoconfiguration(TranslatorAwareInterface::class)
            ->addTag('docusign.translator.aware');

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
                    '$accountApiUri' => $value['demo'] ? JwtGrant::DEMO_ACCOUNT_API_URI : JwtGrant::ACCOUNT_API_URI,
                    '$ttl' => $value['auth_jwt']['ttl'],
                ]);

            // CreateDocument
            $container->register("docusign.create_document.$name", CreateDocument::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                ])
                ->addTag('docusign.envelope_builder.action', ['priority' => -2]);

            // DefineEnvelope
            $container->register("docusign.define_envelope.$name", DefineEnvelope::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                ])
                ->addTag('docusign.envelope_builder.action', ['priority' => -4]);

            $container->register("docusign.send_envelope.$name", SendEnvelope::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                    '$grant' => new Reference("docusign.grant.$name"),
                ])
                ->addTag('docusign.envelope_builder.action', ['priority' => -8]);

            // CreateRecipient
            $container->register("docusign.create_recipient.$name", CreateRecipient::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                ])
                ->addTag('docusign.envelope_builder.action', ['priority' => -16]);

            // EnvelopeCreator
            $container->register("docusign.envelope_creator.$name", EnvelopeCreator::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$actions' => new TaggedIteratorArgument('docusign.envelope_builder.action'),
                    '$signatureName' => $name,
                ])
                ->addTag('docusign.envelope_creator');

            // Envelope builder
            $container->register("docusign.envelope_builder.$name", EnvelopeBuilder::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$storage' => new Reference($value['storage']['storage']),
                    '$envelopeCreator' => new Reference("docusign.envelope_creator.$name"),
                    '$accountId' => $value['account_id'],
                    '$defaultSignerName' => $value['default_signer_name'],
                    '$defaultSignerEmail' => $value['default_signer_email'],
                    '$apiUri' => $value['api_uri'],
                    '$callback' => $value['callback'],
                    '$mode' => $value['mode'],
                    '$name' => $name,
                ]);

            // Signature extractor
            $container->register("docusign.signature_extractor.$name", SignatureExtractor::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([
                    '$isOverridable' => $value['signatures_overridable'],
                    '$signatures' => $value['signatures'],
                ]);

            if (!empty($value['auth_jwt'])) {
                $container->register("docusign.consent.$name", Consent::class)
                    ->setAutowired(true)
                    ->setPublic(true)
                    ->setArguments([
                        '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                        '$consentUri' => $value['demo'] ? Consent::DEMO_CONSENT_URI : Consent::CONSENT_URI,
                        '$integrationKey' => $value['auth_jwt']['integration_key'],
                    ]);

                if (null === $default) {
                    $container->setAlias(Consent::class, new Alias("docusign.consent.$name"));
                }
            }

            if (null === $default) {
                $container->setAlias(EnvelopeBuilderInterface::class, new Alias("docusign.envelope_builder.$name"));
                $container->setAlias(EnvelopeBuilder::class, new Alias("docusign.envelope_builder.$name"));
                $container->setAlias(SignatureExtractor::class, new Alias("docusign.signature_extractor.$name"));
                $container->setAlias(JwtGrant::class, new Alias("docusign.grant.$name"));
                $container->setAlias(GrantInterface::class, new Alias("docusign.grant.$name"));
                $container->setAlias(CreateDocument::class, new Alias("docusign.create_document.$name"));
                $container->setAlias(DefineEnvelope::class, new Alias("docusign.define_envelope.$name"));
                $container->setAlias(SendEnvelope::class, new Alias("docusign.send_envelope.$name"));
                $container->setAlias(CreateRecipient::class, new Alias("docusign.create_recipient.$name"));
                $container->setAlias(EnvelopeCreator::class, new Alias("docusign.envelope_creator.$name"));

                $default = $name;
            }
        }

        $container->register('docusign.route_loader', DocusignLoader::class)
            ->setArgument('$config', $config)
            ->setPublic(false)
            ->addTag('routing.loader');

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
