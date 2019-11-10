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

use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Consent;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Routing\DocusignLoader;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class DocusignExtensionTest extends TestCase
{
    public const DEFAULT_CONFIG = ['docusign' => [
        'demo' => false,
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

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -2])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -4])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -8])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -16])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_creator')->shouldBeCalled();

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

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.consent.default', Consent::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Consent::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilderProphecy->register('docusign.create_document.default', EnvelopeCreator\CreateDocument::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.create_recipient.default', EnvelopeCreator\CreateRecipient::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.define_envelope.default', EnvelopeCreator\DefineEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.send_envelope.default', EnvelopeCreator\SendEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.envelope_creator.default', EnvelopeCreator\EnvelopeCreator::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateDocument::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\DefineEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\SendEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateRecipient::class, Argument::type(Alias::class))->shouldBeCalled();
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

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        }

        $childDefinitionProphecyMock = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecyMock->addTag('docusign.envelope_builder.action')->shouldBeCalled();
        $childDefinitionProphecyMock->addTag('docusign.translator.aware')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration(EnvelopeCreator\EnvelopeBuilderCallableInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());
        $containerBuilderProphecy->registerForAutoconfiguration(TranslatorAwareInterface::class)->shouldBeCalled()->willReturn($childDefinitionProphecyMock->reveal());

        $aliasDefinition = $this->prophesize(Alias::class);
        $aliasDefinition->setPublic(false)->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -2])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -4])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -8])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_builder.action', ['priority' => -16])->shouldBeCalled();
        $definitionProphecy->addTag('docusign.envelope_creator')->shouldBeCalled();

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

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('flysystem.adapter.default', Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());
        $containerBuilderProphecy->register('docusign.consent.default', Consent::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(Consent::class, Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());

        $containerBuilderProphecy->register('docusign.create_document.default', EnvelopeCreator\CreateDocument::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.create_recipient.default', EnvelopeCreator\CreateRecipient::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.define_envelope.default', EnvelopeCreator\DefineEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.send_envelope.default', EnvelopeCreator\SendEnvelope::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->register('docusign.envelope_creator.default', EnvelopeCreator\EnvelopeCreator::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('flysystem.storage.default', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilderInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateDocument::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\DefineEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\SendEnvelope::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\CreateRecipient::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeCreator\EnvelopeCreator::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEMO_CONFIG, $containerBuilder);
    }
}
