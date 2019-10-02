<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection\Compiler;

use DocusignBundle\DependencyInjection\Compiler\EnvelopeBuilderPass;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Exception\MissingStorageException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EnvelopeBuilderPassTest extends TestCase
{
    public function testProcess(): void
    {
        $envelopeBuilderPass = new EnvelopeBuilderPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $envelopeBuilderPass);

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setArgument('$logger', Argument::type(Reference::class))->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$router', Argument::type(Reference::class))->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$docusignStorage', Argument::type(Reference::class))->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$accessToken', 'docusign_access_token')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$accountId', 'docusign_account_id')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerName', 'docusign_default_signer_name')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerEmail', 'docusign_default_signer_email')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$apiURI', 'docusign_api_uri')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$callBackRouteName', 'docusign_callback_route_name')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$webhookRouteName', 'docusign_webhook_route_name')->shouldBeCalled();

        $clientDefinitionProphecy->getArgument('$docusignStorage')->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());

        $containerBuilderProphecy->findTaggedServiceIds('flysystem.storage')->shouldBeCalled()->willReturn(['docusign.storage'=>'flysystem.storage']);

        $containerBuilderProphecy->getParameter('docusign.access_token')->shouldBeCalled()->willReturn('docusign_access_token');
        $containerBuilderProphecy->getParameter('docusign.account_id')->shouldBeCalled()->willReturn('docusign_account_id');
        $containerBuilderProphecy->getParameter('docusign.default_signer_name')->shouldBeCalled()->willReturn('docusign_default_signer_name');
        $containerBuilderProphecy->getParameter('docusign.default_signer_email')->shouldBeCalled()->willReturn('docusign_default_signer_email');
        $containerBuilderProphecy->getParameter('docusign.api_uri')->shouldBeCalled()->willReturn('docusign_api_uri');
        $containerBuilderProphecy->getParameter('docusign.callback_route_name')->shouldBeCalled()->willReturn('docusign_callback_route_name');
        $containerBuilderProphecy->getParameter('docusign.webhook_route_name')->shouldBeCalled()->willReturn('docusign_webhook_route_name');

        $envelopeBuilderPass->process($containerBuilderProphecy->reveal());
    }

    public function testMissingStorageProcess(): void
    {
        $envelopeBuilderPass = new EnvelopeBuilderPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $envelopeBuilderPass);

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setArgument('$logger', Argument::type(Reference::class))->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$router', Argument::type(Reference::class))->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$docusignStorage', Argument::type(Reference::class))->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$accessToken', 'docusign_accessToken')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$accountId', 'docusign_accountId')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerName', 'docusign_signerName')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerEmail', 'docusign_signerEmail')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$apiURI', 'docusign_apiURI')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$callBackRouteName', 'docusign_callbackRouteName')->shouldNotBeCalled();
        $clientDefinitionProphecy->setArgument('$webHookRouteName', 'docusign_webHookRouteName')->shouldNotBeCalled();

        $clientDefinitionProphecy->getArgument('$docusignStorage')->willThrow(OutOfBoundsException::class)->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());

        $containerBuilderProphecy->findTaggedServiceIds('flysystem.storage')->shouldBeCalled()->willReturn([]);

        $containerBuilderProphecy->getParameter('docusign.accessToken')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.accountId')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.defaultSignerName')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.defaultSignerEmail')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.apiURI')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.callbackRouteName')->shouldNotBeCalled();
        $containerBuilderProphecy->getParameter('docusign.webHookRouteName')->shouldNotBeCalled();

        $this->expectException(MissingStorageException::class);
        $envelopeBuilderPass->process($containerBuilderProphecy->reveal());
    }
}
