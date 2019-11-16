<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection\Compiler;

use DocusignBundle\DependencyInjection\Compiler\EnvelopeBuilderPass;
use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
use League\FlysystemBundle\FlysystemBundle;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class EnvelopeBuilderPassTest extends TestCase
{
    public function testProcess(): void
    {
        $envelopeBuilderPass = new EnvelopeBuilderPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $envelopeBuilderPass);

        /** @var Definition|ObjectProphecy $clientDefinitionProphecy */
        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setAutowired(true)->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$accountId', 'docusign_account_id')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerName', 'docusign_default_signer_name')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerEmail', 'docusign_default_signer_email')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$apiURI', 'docusign_api_uri')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$callBackRouteName', 'docusign_callback_route_name')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$webhookRouteName', 'docusign_webhook_route_name')->shouldBeCalled();

        /** @var ContainerBuilder|ObjectProphecy $containerBuilderProphecy */
        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());

        $containerBuilderProphecy->getParameter('docusign.account_id')->shouldBeCalled()->willReturn('docusign_account_id');
        $containerBuilderProphecy->getParameter('docusign.default_signer_name')->shouldBeCalled()->willReturn('docusign_default_signer_name');
        $containerBuilderProphecy->getParameter('docusign.default_signer_email')->shouldBeCalled()->willReturn('docusign_default_signer_email');
        $containerBuilderProphecy->getParameter('docusign.api_uri')->shouldBeCalled()->willReturn('docusign_api_uri');
        $containerBuilderProphecy->getParameter('docusign.callback_route_name')->shouldBeCalled()->willReturn('docusign_callback_route_name');
        $containerBuilderProphecy->getParameter('docusign.webhook_route_name')->shouldBeCalled()->willReturn('docusign_webhook_route_name');

        if (!class_exists(FlysystemBundle::class)) {
            $containerBuilderProphecy->findTaggedServiceIds('flysystem.storage')->willReturn([
                DocusignExtension::STORAGE_NAME => [['name' => 'flysystem.storage']],
            ]);
            $clientDefinitionProphecy->setArgument('$docusignStorage', Argument::type(Reference::class))->shouldBeCalled();
            $clientDefinitionProphecy->getArgument('$docusignStorage')->shouldBeCalled();
            $clientDefinitionProphecy->setArgument('$logger', Argument::type(Reference::class))->shouldBeCalled();
            $clientDefinitionProphecy->setArgument('$stopwatch', Argument::type(Reference::class))->shouldBeCalled();
            $clientDefinitionProphecy->setArgument('$router', Argument::type(Reference::class))->shouldBeCalled();
        }

        $envelopeBuilderPass->process($containerBuilderProphecy->reveal());
    }

}
