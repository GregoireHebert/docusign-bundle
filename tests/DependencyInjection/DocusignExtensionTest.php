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
use League\Flysystem\PluginInterface;
use League\FlysystemBundle\FlysystemBundle;
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
            'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
            'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
        ],
        'account_id' => 1234567,
        'default_signer_name' => 'Grégoire Hébert',
        'default_signer_email' => 'gregoire@les-tilleuls.coop',
    ]];
    public const DEMO_CONFIG = ['docusign' => [
        'demo' => true,
        'auth_jwt' => [
            'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
            'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
            'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
        ],
        'account_id' => 1234567,
        'default_signer_name' => 'Grégoire Hébert',
        'default_signer_email' => 'gregoire@les-tilleuls.coop',
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

        if (!class_exists(FlysystemBundle::class)) {
            $childDefinitionProphecy = $this->prophesize(ChildDefinition::class);
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->willReturn($childDefinitionProphecy)->shouldBeCalled();
            $childDefinitionProphecy->addTag('flysystem.plugin')->shouldBeCalled();
        }

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        }

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_envelope_builder', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_signature_extractor', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_grant_jwt', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilderProphecy->setParameter('docusign.auth_jwt.private_key', '%kernel.project_dir%/var/jwt/docusign.pem')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.integration_key', 'c9763e9d-74e1-4370-9889-d749efd2b2ac')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.user_guid', '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.account_id', 1234567)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.ttl', 3600)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_name', 'Grégoire Hébert')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_email', 'gregoire@les-tilleuls.coop')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.api_uri', 'https://www.docusign.net/restapi')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.account_api_uri', 'https://account.docusign.com/oauth/token')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.callback_route_name', 'docusign_callback')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.webhook_route_name', 'docusign_webhook')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures_overridable', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures', [])->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }

    public function testLoadDemoConfig(): void
    {
        $containerBuilderProphecy = $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);

        $containerBuilderProphecy->hasExtension('http://symfony.com/schema/dic/services')->willReturn(false);

        if (!class_exists(FlysystemBundle::class)) {
            $childDefinitionProphecy = $this->prophesize(ChildDefinition::class);
            $containerBuilderProphecy->registerForAutoconfiguration(PluginInterface::class)->willReturn($childDefinitionProphecy)->shouldBeCalled();
            $childDefinitionProphecy->addTag('flysystem.plugin')->shouldBeCalled();
        }

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        if (method_exists(ContainerBuilder::class, 'removeBindings')) {
            $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});
        }

        $containerBuilderProphecy->setDefinition('docusign_callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Callback::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Sign::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_webhook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(Webhook::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_envelope_builder', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(EnvelopeBuilder::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_signature_extractor', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(SignatureExtractor::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('docusign_grant_jwt', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(JwtGrant::class, Argument::type(Alias::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(GrantInterface::class, Argument::type(Alias::class))->shouldBeCalled();

        $containerBuilderProphecy->setParameter('docusign.auth_jwt.private_key', '%kernel.project_dir%/var/jwt/docusign.pem')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.integration_key', 'c9763e9d-74e1-4370-9889-d749efd2b2ac')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.user_guid', '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.account_id', 1234567)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.auth_jwt.ttl', 3600)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_name', 'Grégoire Hébert')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_email', 'gregoire@les-tilleuls.coop')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.api_uri', 'https://demo.docusign.net/restapi')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.account_api_uri', 'https://account-d.docusign.com/oauth/token')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.callback_route_name', 'docusign_callback')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.webhook_route_name', 'docusign_webhook')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures_overridable', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures', [])->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEMO_CONFIG, $containerBuilder);
    }
}
