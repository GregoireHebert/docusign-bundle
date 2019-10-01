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
        $definition->setArgument('$accessToken', $container->getParameter('docusign.access_token'));
        $definition->setArgument('$accountId', $container->getParameter('docusign.account_id'));
        $definition->setArgument('$defaultSignerName', $container->getParameter('docusign.default_signer_name'));
        $definition->setArgument('$defaultSignerEmail', $container->getParameter('docusign.default_signer_email'));
        $definition->setArgument('$apiURI', $container->getParameter('docusign.api_uri'));
        $definition->setArgument('$callBackRouteName', $container->getParameter('docusign.callback_route_name'));
        $definition->setArgument('$webhookRouteName', $container->getParameter('docusign.webhook_route_name'));
    }
}
