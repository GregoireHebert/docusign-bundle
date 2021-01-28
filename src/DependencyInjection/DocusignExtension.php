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
use DocusignBundle\Controller\AuthorizationCode;
use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Consent;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\CreateDocument;
use DocusignBundle\EnvelopeCreator\CreateRecipient;
use DocusignBundle\EnvelopeCreator\CreateSignature;
use DocusignBundle\EnvelopeCreator\DefineEnvelope;
use DocusignBundle\EnvelopeCreator\EnvelopeBuilderCallableInterface;
use DocusignBundle\EnvelopeCreator\EnvelopeCreator;
use DocusignBundle\EnvelopeCreator\GetViewUrl;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\EnvelopeCreator\TraceableEnvelopeBuilderCallable;
use DocusignBundle\EventSubscriber\AuthorizationCodeEventSubscriber;
use DocusignBundle\Grant\AuthorizationCodeGrant;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Routing\DocusignLoader;
use DocusignBundle\TokenEncoder\TokenEncoder;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Twig\Extension\ClickwrapExtension;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\Filesystem;
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
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

        $container->register('docusign.route_loader', DocusignLoader::class)
            ->setArgument('$config', $config)
            ->setPublic(false)
            ->addTag('routing.loader');

        $clickwrapExtensionDefinition = $container->register('docusign.twig.extension.clickwrap', ClickwrapExtension::class)
            ->setPublic(false)
            ->addTag('twig.extension');

        $default = null;

        foreach ($config as $name => $value) {
            // Clickwrap mode
            if (EnvelopeBuilder::MODE_CLICKWRAP === $value['mode']) {
                $clickwrapExtensionDefinition->addMethodCall('addConfig', [$name, $value['demo'], [
                    'environment' => pathinfo($value['api_uri'], \PATHINFO_DIRNAME),
                    'accountId' => $value['auth_clickwrap']['api_account_id'],
                    'clientUserId' => $value['auth_clickwrap']['user_guid'],
                    'clickwrapId' => $value['auth_clickwrap']['clickwrap_id'],
                ]]);
                continue;
            }

            // Embedded/Remote mode

            $auth = $value['auth_jwt'] ?? $value['auth_code'];
            $isAuthJwt = \array_key_exists('auth_jwt', $value);

            if (empty($value['callback'])) {
                $value['callback'] = "docusign_callback_$name";
            }

            // Storage (FlySystem compatibility)
            if (!isset($value['storage']['storage'])) {
                $value['storage']['storage'] = $this->flySystemCompatibility($container, $name, $value['storage']);
            }

            // Token encoder
            $container->register("docusign.token_encoder.$name", TokenEncoder::class)
                ->setPublic(false)
                ->setArguments([
                    '$integrationKey' => $auth['integration_key'],
                ]);

            // Grant
            if ($isAuthJwt) {
                $container->register("docusign.grant.$name", JwtGrant::class)
                    ->setAutowired(true)
                    ->setPublic(false)
                    ->setArguments([
                        '$privateKey' => $auth['private_key'],
                        '$integrationKey' => $auth['integration_key'],
                        '$userGuid' => $auth['user_guid'],
                        '$demo' => $value['demo'],
                        '$ttl' => $auth['ttl'],
                    ]);

                if (null === $default) {
                    $container->setAlias(JwtGrant::class, new Alias("docusign.grant.$name"));
                }
            } else {
                $container->register("docusign.grant.$name", AuthorizationCodeGrant::class)
                    ->setAutowired(true)
                    ->setPublic(false)
                    ->setArguments([
                        '$authorizationCodeHandler' => new Reference($auth['strategy']),
                        '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                    ]);

                $container->register("docusign.event_subscriber.authorization_code.$name", AuthorizationCodeEventSubscriber::class)
                    ->setAutowired(true)
                    ->setPublic(false)
                    ->setArguments([
                        '$authorizationCodeHandler' => new Reference($auth['strategy']),
                        '$tokenEncoder' => new Reference("docusign.token_encoder.$name"),
                        '$integrationKey' => $auth['integration_key'],
                        '$secret' => $auth['secret'],
                        '$demo' => $value['demo'],
                    ])
                    ->addTag('kernel.event_subscriber');

                if (null === $default) {
                    $container->setAlias(AuthorizationCodeGrant::class, new Alias("docusign.grant.$name"));
                    $container->setAlias(AuthorizationCodeEventSubscriber::class, new Alias("docusign.event_subscriber.authorization_code.$name"));
                }
            }

            $this->createActions($container, $name);

            if (false !== $value['enable_profiler']) {
                $this->setActionsTraceable($container, $name);
            }

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
                    '$demo' => $value['demo'],
                    '$apiUri' => $value['api_uri'],
                    '$callback' => $value['callback'],
                    '$mode' => $value['mode'],
                    '$authMode' => $isAuthJwt ? EnvelopeBuilder::AUTH_MODE_JWT : EnvelopeBuilder::AUTH_MODE_CODE,
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

            // Update Sign controller
            $container->register("docusign.sign.$name", Sign::class)
                ->setPublic(true)
                ->setArguments([
                    '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                ])->addTag('controller.service_arguments');

            $container->register("docusign.webhook.$name", Webhook::class)
                ->setPublic(true)
                ->setArguments([
                    '$tokenEncoder' => new Reference("docusign.token_encoder.$name"),
                ])->addTag('controller.service_arguments');

            if (!preg_match('/^https?:\/\//', $value['callback'])) {
                $container->register("docusign.callback.$name", Callback::class)
                    ->setPublic(true)
                    ->addTag('controller.service_arguments');
            }

            if ($isAuthJwt) {
                $container->register("docusign.consent.$name", Consent::class)
                    ->setPublic(true)
                    ->setArguments([
                        '$demo' => $value['demo'],
                        '$integrationKey' => $auth['integration_key'],
                    ])->addTag('controller.service_arguments');

                if (null === $default) {
                    $container->setAlias(Consent::class, new Alias("docusign.consent.$name"));
                }
            } else {
                $container->register("docusign.authorization_code.$name", AuthorizationCode::class)
                    ->setPublic(true)
                    ->setArguments([
                        '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                        '$tokenEncoder' => new Reference("docusign.token_encoder.$name"),
                    ])->addTag('controller.service_arguments');

                if (null === $default) {
                    $container->setAlias(AuthorizationCode::class, new Alias("docusign.authorization_code.$name"));
                }
            }

            if (null === $default) {
                $container->setAlias(Sign::class, new Alias("docusign.sign.$name"));
                $container->setAlias(Webhook::class, new Alias("docusign.webhook.$name"));
                $container->setAlias(Callback::class, new Alias("docusign.callback.$name"));
                $container->setAlias(EnvelopeBuilderInterface::class, new Alias("docusign.envelope_builder.$name"));
                $container->setAlias(EnvelopeBuilder::class, new Alias("docusign.envelope_builder.$name"));
                $container->setAlias(SignatureExtractor::class, new Alias("docusign.signature_extractor.$name"));
                $container->setAlias(GrantInterface::class, new Alias("docusign.grant.$name"));
                $container->setAlias(CreateDocument::class, new Alias("docusign.create_document.$name"));
                $container->setAlias(CreateSignature::class, new Alias("docusign.create_signature.$name"));
                $container->setAlias(DefineEnvelope::class, new Alias("docusign.define_envelope.$name"));
                $container->setAlias(SendEnvelope::class, new Alias("docusign.send_envelope.$name"));
                $container->setAlias(GetViewUrl::class, new Alias("docusign.get_view_url.$name"));
                $container->setAlias(CreateRecipient::class, new Alias("docusign.create_recipient.$name"));
                $container->setAlias(EnvelopeCreator::class, new Alias("docusign.envelope_creator.$name"));
                $container->setAlias(TokenEncoderInterface::class, new Alias("docusign.token_encoder.$name"));

                $default = $name;
            }
        }
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

    private function createActions(ContainerBuilder $container, string $name): void
    {
        // CreateSignature
        $container->register("docusign.create_signature.$name", CreateSignature::class)
            ->setAutowired(false)
            ->setPublic(false)
            ->setArguments([
                '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
                '$signatureExtractor' => new Reference("docusign.signature_extractor.$name"),
            ])
            ->addTag('docusign.envelope_builder.action', ['priority' => 0]);

        // CreateDocument
        $container->register("docusign.create_document.$name", CreateDocument::class)
            ->setAutowired(false)
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
                '$tokenEncoder' => new Reference("docusign.token_encoder.$name"),
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

        // GetViewUrl
        $container->register("docusign.get_view_url.$name", GetViewUrl::class)
            ->setAutowired(true)
            ->setPublic(false)
            ->setArguments([
                '$envelopeBuilder' => new Reference("docusign.envelope_builder.$name"),
            ])
            ->addTag('docusign.envelope_builder.action', ['priority' => -16]);
        $container->setAlias("docusign.create_recipient.$name", new Alias("docusign.get_view_url.$name"));
    }

    private function setActionsTraceable(ContainerBuilder $container, string $name): void
    {
        $container->register("docusign.decorated_create_document.$name", TraceableEnvelopeBuilderCallable::class)
            ->setAutowired(true)
            ->setDecoratedService("docusign.create_document.$name")
            ->addArgument(new Reference("docusign.decorated_create_document.$name.inner"))
            ->setPublic(false)
        ;

        $container->register("docusign.decorated_create_signature.$name", TraceableEnvelopeBuilderCallable::class)
            ->setAutowired(true)
            ->setDecoratedService("docusign.create_signature.$name")
            ->addArgument(new Reference("docusign.decorated_create_signature.$name.inner"))
            ->setPublic(false)
        ;

        $container->register("docusign.decorated_define_envelope.$name", TraceableEnvelopeBuilderCallable::class)
            ->setAutowired(true)
            ->setDecoratedService("docusign.define_envelope.$name")
            ->addArgument(new Reference("docusign.decorated_define_envelope.$name.inner"))
            ->setPublic(false)
        ;

        $container->register("docusign.decorated_send_envelope.$name", TraceableEnvelopeBuilderCallable::class)
            ->setAutowired(true)
            ->setDecoratedService("docusign.send_envelope.$name")
            ->addArgument(new Reference("docusign.decorated_send_envelope.$name.inner"))
            ->setPublic(false)
        ;

        $container->register("docusign.decorated_create_recipient.$name", TraceableEnvelopeBuilderCallable::class)
            ->setAutowired(true)
            ->setDecoratedService("docusign.get_view_url.$name")
            ->addArgument(new Reference("docusign.decorated_create_recipient.$name.inner"))
            ->setPublic(false)
        ;
    }
}
