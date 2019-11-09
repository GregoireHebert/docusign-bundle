<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Grant\GrantInterface;
use DocusignBundle\Grant\JwtGrant;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class DocusignExtensionTest extends TestCase
{
    public const DEFAULT_CONFIG = ['docusign' => [
        'demo' => false,
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
            'options' => []
        ]
    ]];
    public const DEMO_CONFIG = ['docusign' => [
        'demo' => true,
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
            'options' => []
        ]
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

    public function testLoadDefaultConfig(): void
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

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->willReturn($definitionProphecy->reveal())->shouldBeCalled();
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testLoadDemoConfig(): void
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

        $aliasDefinition = $this->prophesize(Alias::class);
        $aliasDefinition->setPublic(false)->shouldBeCalled();

        $definitionProphecy = $this->prophesize(Definition::class);
        $definitionProphecy->setAutowired(true)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setPublic(false)->shouldBeCalled()->willReturn($definitionProphecy);
        $definitionProphecy->setArguments(Argument::type('array'))->shouldBeCalled()->willReturn($definitionProphecy);

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias('flysystem.adapter.default', Argument::type(Alias::class))->shouldBeCalled()->willReturn($aliasDefinition->reveal());

        $containerBuilderProphecy->register('docusign.envelope_builder.default', EnvelopeBuilder::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.signature_extractor.default', SignatureExtractor::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->register('docusign.grant.default', JwtGrant::class)->shouldBeCalled()->willReturn($definitionProphecy->reveal());
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('flysystem.storage.default', Argument::type(Definition::class))->shouldBeCalled();

        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEMO_CONFIG, $containerBuilder);
    }
}
