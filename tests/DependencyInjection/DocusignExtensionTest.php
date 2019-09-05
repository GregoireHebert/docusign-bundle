<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\DependencyInjection\DocusignExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Config\Resource\GlobResource;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\EnvPlaceholderParameterBag;

class DocusignExtensionTest extends TestCase
{
    public const DEFAULT_CONFIG = ['docusign' => [
        'accessToken' => 'token',
        'accountId' => 'ID',
        'defaultSignerName' => 'Grégoire Hébert',
        'defaultSignerEmail' => 'gregoire@les-tilleuls.coop',
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

        $parameterBag = new EnvPlaceholderParameterBag();
        $containerBuilderProphecy->getParameterBag()->willReturn($parameterBag);

        $containerBuilderProphecy->getReflectionClass(Argument::type('string'))->will(function ($args) {
            return new \ReflectionClass($args[0]);
        })->shouldBeCalled();

        $containerBuilderProphecy->fileExists(Argument::type('string'))->will(function ($args) {
            return file_exists($args[0]);
        })->shouldBeCalled();

        $containerBuilderProphecy->addResource(Argument::type(GlobResource::class))->shouldBeCalled();
        $containerBuilderProphecy->removeBindings(Argument::type('string'))->will(function (): void {});

        $containerBuilderProphecy->setDefinition('DocusignBundle\Controller\Callback', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('DocusignBundle\Controller\Sign', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('DocusignBundle\Controller\WebHook', Argument::type(Definition::class))->shouldBeCalled();
        $containerBuilderProphecy->setDefinition('DocusignBundle\EnvelopeBuilder', Argument::type(Definition::class))->shouldBeCalled();

        $containerBuilderProphecy->setParameter('docusign.accessToken', 'token')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.accountId', 'ID')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.defaultSignerName', 'Grégoire Hébert')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.defaultSignerEmail', 'gregoire@les-tilleuls.coop')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.apiURI', 'https://demo.docusign.net/restapi')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.callBackRouteName', 'docusign_callback')->shouldBeCalled();
        $containerBuilderProphecy->setParameter('docusign.webHookRouteName', 'docusign_webhook')->shouldBeCalled();
        $containerBuilder = $containerBuilderProphecy->reveal();

        $this->extension->load(self::DEFAULT_CONFIG, $containerBuilder);
    }
}
