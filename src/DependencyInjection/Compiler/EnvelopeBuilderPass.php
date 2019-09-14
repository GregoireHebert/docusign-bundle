<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EnvelopeBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(EnvelopeBuilder::class);
        $definition->setAutowired(true);
        $definition->setArgument('$accessToken', $container->getParameter('docusign.accessToken'));
        $definition->setArgument('$accountId', $container->getParameter('docusign.accountId'));
        $definition->setArgument('$defaultSignerName', $container->getParameter('docusign.defaultSignerName'));
        $definition->setArgument('$defaultSignerEmail', $container->getParameter('docusign.defaultSignerEmail'));
        $definition->setArgument('$apiURI', $container->getParameter('docusign.apiURI'));
        $definition->setArgument('$callBackRouteName', $container->getParameter('docusign.callbackRouteName'));
        $definition->setArgument('$webHookRouteName', $container->getParameter('docusign.webHookRouteName'));
    }
}
