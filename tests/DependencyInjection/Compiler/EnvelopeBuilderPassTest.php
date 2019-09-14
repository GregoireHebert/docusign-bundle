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
        $clientDefinitionProphecy->setAutowired(true)->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$accessToken', 'docusign_accessToken')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$accountId', 'docusign_accountId')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerName', 'docusign_signerName')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$defaultSignerEmail', 'docusign_signerEmail')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$apiURI', 'docusign_apiURI')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$callBackRouteName', 'docusign_callbackRouteName')->shouldBeCalled();
        $clientDefinitionProphecy->setArgument('$webHookRouteName', 'docusign_webHookRouteName')->shouldBeCalled();


        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());


        $containerBuilderProphecy->getParameter('docusign.accessToken')->shouldBeCalled()->willReturn('docusign_accessToken');
        $containerBuilderProphecy->getParameter('docusign.accountId')->shouldBeCalled()->willReturn('docusign_accountId');
        $containerBuilderProphecy->getParameter('docusign.defaultSignerName')->shouldBeCalled()->willReturn('docusign_signerName');
        $containerBuilderProphecy->getParameter('docusign.defaultSignerEmail')->shouldBeCalled()->willReturn('docusign_signerEmail');
        $containerBuilderProphecy->getParameter('docusign.apiURI')->shouldBeCalled()->willReturn('docusign_apiURI');
        $containerBuilderProphecy->getParameter('docusign.callbackRouteName')->shouldBeCalled()->willReturn('docusign_callbackRouteName');
        $containerBuilderProphecy->getParameter('docusign.webHookRouteName')->shouldBeCalled()->willReturn('docusign_webHookRouteName');

        $envelopeBuilderPass->process($containerBuilderProphecy->reveal());
    }

}
