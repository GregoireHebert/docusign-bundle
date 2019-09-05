<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection\Compiler;

use DocusignBundle\Bridge\FlySystem\Adapter\SharePointAdapter;
use DocusignBundle\Bridge\FlySystem\DocusignStorage;
use DocusignBundle\DependencyInjection\Compiler\DocusignStoragePass;
use DocusignBundle\EnvelopeBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DocusignStoragePassTest extends TestCase
{
    public function testProcessWithCustomStorageService(): void
    {
        $docusignStoragePass = new DocusignStoragePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $docusignStoragePass);

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setArgument('$docusignStorage', Argument::type(Reference::class))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->has(DocusignStoragePass::DOCUSIGN_STORAGE_SERVICE_ALIAS)->shouldBeCalled()->willReturn(true);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());

        $docusignStoragePass->process($containerBuilderProphecy->reveal());
    }

    public function testProcessWithDefaultStorageService(): void
    {
        $docusignStoragePass = new DocusignStoragePass();

        $this->assertInstanceOf(CompilerPassInterface::class, $docusignStoragePass);

        $clientDefinitionProphecy = $this->prophesize(Definition::class);
        $clientDefinitionProphecy->setArgument('$docusignStorage', Argument::type(Reference::class))->shouldBeCalled();

        $containerBuilderProphecy = $this->prophesize(ContainerBuilder::class);
        $containerBuilderProphecy->setDefinition(SharePointAdapter::class, Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition(DocusignStorage::class, Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setAlias(DocusignStoragePass::DOCUSIGN_STORAGE_SERVICE_ALIAS, DocusignStorage::class)->shouldBeCalled();

        $containerBuilderProphecy->has(DocusignStoragePass::DOCUSIGN_STORAGE_SERVICE_ALIAS)->shouldBeCalled()->willReturn(false);
        $containerBuilderProphecy->findDefinition(EnvelopeBuilder::class)->shouldBeCalled()->willReturn($clientDefinitionProphecy->reveal());

        $docusignStoragePass->process($containerBuilderProphecy->reveal());
    }
}
