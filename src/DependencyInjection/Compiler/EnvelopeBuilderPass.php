<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use DocusignBundle\EnvelopeBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\RouterInterface;

final class EnvelopeBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(EnvelopeBuilder::class);

        $definition->setArgument('$logger', new Reference(LoggerInterface::class));
        $definition->setArgument('$router', new Reference(RouterInterface::class));
        $definition->setArgument('$accessToken', $container->getParameter('docusign.accessToken'));
        $definition->setArgument('$accountId', $container->getParameter('docusign.accountId'));
        $definition->setArgument('$defaultSignerName', $container->getParameter('docusign.defaultSignerName'));
        $definition->setArgument('$defaultSignerEmail', $container->getParameter('docusign.defaultSignerEmail'));
        $definition->setArgument('$apiURI', $container->getParameter('docusign.apiURI'));
        $definition->setArgument('$callBackRouteName', $container->getParameter('docusign.callbackRouteName'));
        $definition->setArgument('$webHookRouteName', $container->getParameter('docusign.webHookRouteName'));
    }
}
