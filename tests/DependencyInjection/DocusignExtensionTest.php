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

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\Controller\AuthorizationCode;
use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Consent;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator;
use DocusignBundle\EventSubscriber\AuthorizationCodeEventSubscriber;
use DocusignBundle\Grant\AuthorizationCodeGrant;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Routing\DocusignLoader;
use DocusignBundle\Tests\ProphecyTrait;
use DocusignBundle\TokenEncoder\TokenEncoder;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Twig\Extension\ClickwrapExtension;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;
use Symfony\Component\DependencyInjection\Reference;

class DocusignExtensionTest extends TestCase
{
    use ProphecyTrait;

    public const DEFAULT_CONFIG = ['docusign' => [
        'demo' => false,
        'enable_profiler' => false,
        'mode' => 'embedded',
        'auth_jwt' => [
            'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
            'integration_key' => 'f19cf430-4a00-491a-9dfe-af1a24323513',
            'user_guid' => 'c4ba97f8-a3a9-4c1e-b4a8-ee529763bada',
        ],
        'account_id' => 5625922,
        'default_signer_name' => 'Grégoire Hébert',
        'default_signer_email' => 'gregoire@les-tilleuls.coop',
        'storage' => [
            'storage' => 'dummy.default.storage',
            'options' => [],
        ],
        'sign_path' => '/foo/sign',
    ]];
    public const DEMO_CONFIG = ['docusign' => [
        'demo' => true,
        'enable_profiler' => true,
        'mode' => 'embedded',
        'auth_jwt' => [
            'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
            'integration_key' => '2b616b94-f56a-4221-95cb-a915fe427e5d',
            'user_guid' => '5ea2b503-51af-4059-8b16-21efc6a0577b',
        ],
        'account_id' => 5625922,
        'default_signer_name' => 'Grégoire Hébert',
        'default_signer_email' => 'gregoire@les-tilleuls.coop',
        'storage' => [
            'adapter' => 'dummy.demo.storage',
            'options' => [],
        ],
        'sign_path' => '/foo/sign',
    ]];
    public const AUTH_CODE_CONFIG = ['docusign' => [
        'demo' => true,
        'enable_profiler' => true,
        'mode' => 'embedded',
        'auth_code' => [
            'integration_key' => '2b616b94-f56a-4221-95cb-a915fe427e5d',
            'secret' => '5ea2b503-51af-4059-8b16-21efc6a0577b',
            'strategy' => 'docusign.authorization_code.fake',
        ],
        'account_id' => 5625922,
        'default_signer_name' => 'Grégoire Hébert',
        'default_signer_email' => 'gregoire@les-tilleuls.coop',
        'storage' => [
            'adapter' => 'dummy.demo.storage',
            'options' => [],
        ],
        'sign_path' => '/foo/sign',
    ]];
    public const CLICKWRAP_CONFIG = ['docusign' => [
        'demo' => true,
        'enable_profiler' => true,
        'mode' => 'clickwrap',
        'auth_clickwrap' => [
            'clickwrap_id' => 'f5de48b3-b323-48a3-bfb1-fa375a7db67b',
            'api_account_id' => '797a9f49-cb98-4b0d-85f9-3509bc9cc453',
            'user_guid' => 'c6a6b2f7-40be-4d7d-a4b1-ca438906b77e',
        ],
    ]];

    private $extension;
    private $childDefinitionProphecy;

    protected function setUp(): void
    {
        $this->extension = new DocusignExtension();
        $this->childDefinitionProphecy = $this->prophesize(ChildDefinition::class);
    }

    protected function tearDown(): void
    {
        $this->extension = null;
    }

    public function testItLoadsDefaultConfig(): void
    {
        $containerBuilderProphecy = $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->willReturn(false);

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        } elseif (method_exists(ContainerBuilder::class, 'addRemovedBindingIds')) {
            $containerBuilderProphecy->addRemovedBindingIds(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        if (!class_exists(FlysystemBundle::class)) {
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
            $childDefinitionProphecyMock->addTag('flysystem.plugin')->shouldBeCalled();
        }

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setAutowired(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => 0])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -2])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -4])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -8])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -16])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_creator')->shouldBeCalled();
        $definitionProphecy->addTag('controller.service_arguments')->shouldBeCalled();

        /** @var ObjectProphecy|Definition $loaderDefinitionProphecy */
        $loaderDefinitionProphecy = $this->prophesize(Definition::class);
        $containerBuilderProphecy
            ->register('docusign.route_loader', DocusignLoader::class)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setArgument('$config', Argument::type('array'))
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setPublic(false)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->addTag('routing.loader')
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();

        $containerBuilderProphecy->register('docusign.consent.default', Consent::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Consent::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.webhook.default', Webhook::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.callback.default', Callback::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.sign.default', Sign::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilderProphecy->register('docusign.create_document.default', EnvelopeCreator\CreateDocument::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.create_signature.default', EnvelopeCreator\CreateSignature::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.get_view_url.default', EnvelopeCreator\GetViewUrl::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.define_envelope.default', EnvelopeCreator\DefineEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.send_envelope.default', EnvelopeCreator\SendEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.envelope_creator.default', EnvelopeCreator\EnvelopeCreator::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.twig.extension.clickwrap', ClickwrapExtension::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $definitionProphecy->addTag('twig.extension')->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.token_encoder.default', TokenEncoder::class)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->setAlias('docusign.create_recipient.default', Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateRecipient::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(TokenEncoderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateDocument::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateSignature::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\DefineEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\SendEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\GetViewUrl::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\EnvelopeCreator::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testItLoadsDemoConfig(): void
    {
        $containerBuilderProphecy = $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->willReturn(false);

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        } elseif (method_exists(ContainerBuilder::class, 'addRemovedBindingIds')) {
            $containerBuilderProphecy->addRemovedBindingIds(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        if (!class_exists(FlysystemBundle::class)) {
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
            $childDefinitionProphecyMock->addTag('flysystem.plugin')->shouldBeCalled();
        }

        $aliasDefinition = $this->prophesize(Alias::class);
        $aliasDefinition->setPublic(false)->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setAutowired(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addArgument(Argument::type(Reference::class))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => 0])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -2])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -4])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -8])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -16])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_creator')->shouldBeCalled();
        $definitionProphecy->addTag('controller.service_arguments')->shouldBeCalled();

        $definitionProphecy->setDecoratedService('docusign.create_document.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.create_signature.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.define_envelope.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.send_envelope.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.get_view_url.default')->shouldBeCalled()->willReturn($definitionProphecy);

        /** @var ObjectProphecy|Definition $loaderDefinitionProphecy */
        $loaderDefinitionProphecy = $this->prophesize(Definition::class);
        $containerBuilderProphecy
            ->register('docusign.route_loader', DocusignLoader::class)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setArgument('$config', Argument::type('array'))
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setPublic(false)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->addTag('routing.loader')
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();

        $containerBuilderProphecy->setAlias('flysystem.adapter.default', Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.consent.default', Consent::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Consent::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.webhook.default', Webhook::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.callback.default', Callback::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.sign.default', Sign::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());

        $containerBuilderProphecy->register('docusign.create_document.default', EnvelopeCreator\CreateDocument::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.create_signature.default', EnvelopeCreator\CreateSignature::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.get_view_url.default', EnvelopeCreator\GetViewUrl::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.define_envelope.default', EnvelopeCreator\DefineEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.send_envelope.default', EnvelopeCreator\SendEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.envelope_creator.default', EnvelopeCreator\EnvelopeCreator::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.twig.extension.clickwrap', ClickwrapExtension::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $definitionProphecy->addTag('twig.extension')->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.decorated_create_document.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_create_signature.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_create_recipient.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_define_envelope.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_send_envelope.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.token_encoder.default', TokenEncoder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(TokenEncoderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('flysystem.storage.default', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('docusign.create_recipient.default', Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateDocument::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateSignature::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\DefineEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\SendEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\GetViewUrl::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateRecipient::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\EnvelopeCreator::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEMO_CONFIG, $containerBuilder);
    }

    public function testItLoadsAuthCodeConfig(): void
    {
        $containerBuilderProphecy = $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->willReturn(false);

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        } elseif (method_exists(ContainerBuilder::class, 'addRemovedBindingIds')) {
            $containerBuilderProphecy->addRemovedBindingIds(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        if (!class_exists(FlysystemBundle::class)) {
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
            $childDefinitionProphecyMock->addTag('flysystem.plugin')->shouldBeCalled();
        }

        $aliasDefinition = $this->prophesize(Alias::class);
        $aliasDefinition->setPublic(false)->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setAutowired(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addArgument(Argument::type(Reference::class))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => 0])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -2])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -4])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -8])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -16])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_creator')->shouldBeCalled();
        $definitionProphecy->addTag('controller.service_arguments')->shouldBeCalled();

        $definitionProphecy->setDecoratedService('docusign.create_document.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.create_signature.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.define_envelope.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.send_envelope.default')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setDecoratedService('docusign.get_view_url.default')->shouldBeCalled()->willReturn($definitionProphecy);

        /** @var ObjectProphecy|Definition $loaderDefinitionProphecy */
        $loaderDefinitionProphecy = $this->prophesize(Definition::class);
        $containerBuilderProphecy
            ->register('docusign.route_loader', DocusignLoader::class)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setArgument('$config', Argument::type('array'))
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setPublic(false)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->addTag('routing.loader')
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();

        $containerBuilderProphecy->setAlias('flysystem.adapter.default', Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.webhook.default', Webhook::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.callback.default', Callback::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.sign.default', Sign::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.authorization_code.default', AuthorizationCode::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(AuthorizationCode::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());

        $containerBuilderProphecy->register('docusign.create_document.default', EnvelopeCreator\CreateDocument::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.create_signature.default', EnvelopeCreator\CreateSignature::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.get_view_url.default', EnvelopeCreator\GetViewUrl::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.define_envelope.default', EnvelopeCreator\DefineEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.send_envelope.default', EnvelopeCreator\SendEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.envelope_creator.default', EnvelopeCreator\EnvelopeCreator::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.twig.extension.clickwrap', ClickwrapExtension::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $definitionProphecy->addTag('twig.extension')->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $definitionProphecy->addTag('kernel.event_subscriber')->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.decorated_create_document.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_create_signature.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_create_recipient.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_define_envelope.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.decorated_send_envelope.default', EnvelopeCreator\TraceableEnvelopeBuilderCallable::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', AuthorizationCodeGrant::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.event_subscriber.authorization_code.default', AuthorizationCodeEventSubscriber::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.token_encoder.default', TokenEncoder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(AuthorizationCodeGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(AuthorizationCodeEventSubscriber::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(TokenEncoderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('flysystem.storage.default', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('docusign.create_recipient.default', Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateDocument::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateSignature::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\DefineEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\SendEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\GetViewUrl::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateRecipient::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\EnvelopeCreator::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::AUTH_CODE_CONFIG, $containerBuilder);
    }

    public function testItLoadsClickwrapConfig(): void
    {
        $containerBuilderProphecy = $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->willReturn(false);

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        } elseif (method_exists(ContainerBuilder::class, 'addRemovedBindingIds')) {
            $containerBuilderProphecy->addRemovedBindingIds(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        if (!class_exists(FlysystemBundle::class)) {
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
            $childDefinitionProphecyMock->addTag('flysystem.plugin')->shouldBeCalled();
        }

        /** @var ObjectProphecy|Definition $loaderDefinitionProphecy */
        $loaderDefinitionProphecy = $this->prophesize(Definition::class);
        $containerBuilderProphecy
            ->register('docusign.route_loader', DocusignLoader::class)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setArgument('$config', Argument::type('array'))
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->setPublic(false)
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();
        $loaderDefinitionProphecy
            ->addTag('routing.loader')
            ->willReturn($loaderDefinitionProphecy->reveal())
            ->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $containerBuilderProphecy->register('docusign.twig.extension.clickwrap', ClickwrapExtension::class)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('twig.extension')->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addMethodCall('addConfig', ['default', true, [
            'environment' => 'https://www.docusign.net',
            'accountId' => '797a9f49-cb98-4b0d-85f9-3509bc9cc453',
            'clientUserId' => 'c6a6b2f7-40be-4d7d-a4b1-ca438906b77e',
            'clickwrapId' => 'f5de48b3-b323-48a3-bfb1-fa375a7db67b',
        ]])->shouldBeCalled()->willReturn($definitionProphecy);

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::CLICKWRAP_CONFIG, $containerBuilder);
    }
}
