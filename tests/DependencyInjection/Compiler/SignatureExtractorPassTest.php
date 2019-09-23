<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection\Compiler;

use DocusignBundle\DependencyInjection\Compiler\SignatureExtractorPass;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SignatureExtractorPassTest extends TestCase
{
    public function testProcess(): void
    {
        $signatureExtractorPass = new SignatureExtractorPass();

        $this->assertInstanceOf(CompilerPassInterface::class, $signatureExtractorPass);

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->addMethodCall('setSignaturesOverridable', [true])->shouldBeCalled();
        $clientDefinitionProphecy->addMethodCall('setDefaultSignatures', [[
            'MyDocument' => [
                'signatures' => [
                    'page' => 1,
                    'xPosition' => 200,
                    'yPosition' => 300,
                ],
            ],
        ]])->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->findDefinition(SignatureExtractor::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());
        $containerBuilderProphecy->getParameter('docusign.signatures_overridable')->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->getParameter('docusign.signatures')->shouldBeCalled()->willReturn([
            'MyDocument' => [
                'signatures' => [
                    'page' => 1,
                    'xPosition' => 200,
                    'yPosition' => 300,
                ],
            ],
        ]);

        $signatureExtractorPass->process($containerBuilderProphecy->reveal());
    }
}
