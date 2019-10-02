<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\Controller\Callback;
use DocusignBundle\Controller\Sign;
use DocusignBundle\Controller\Webhook;
use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
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
        'access_token' => 'token',
        'account_id' => 'ID',
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

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        $childDefinitionProphecy = $this->prophesize(ChildDefinition::class);
        $childDefinitionProphecy->addTag('flysystem.plugin')->shouldBeCalled();

        $containerBuilderProphecy->registerForAutoconfiguration('League\Flysystem\PluginInterface')->shouldBeCalled()->willReturn($childDefinitionProphecy->reveal());

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});

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

        $containerBuilderProphecy->setParameter('docusign.access_token', 'token')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.account_id', 'ID')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_name', 'Grégoire Hébert')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.default_signer_email', 'gregoire@les-tilleuls.coop')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.api_uri', 'https://demo.docusign.net/restapi')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.callback_route_name', 'docusign_callback')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.webhook_route_name', 'docusign_webhook')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures_overridable', false)->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.signatures', [])->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }
}
