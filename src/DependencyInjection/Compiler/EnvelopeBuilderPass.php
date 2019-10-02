<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use DocusignBundle\DependencyInjection\DocusignExtension;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Exception\MissingStorageException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\OutOfBoundsException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Routing\RouterInterface;

final class EnvelopeBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(EnvelopeBuilder::class);

        foreach ($storages = $container->findTaggedServiceIds('flysystem.storage') as $id => $tags) {
            if (DocusignExtension::STORAGE_NAME === $id) {
                $definition->setArgument('$docusignStorage', new Reference($id));
            }
        }

        try {
            $definition->getArgument('$docusignStorage');
        } catch (OutOfBoundsException $exception) {
            throw new MissingStorageException();
        }

        $definition->setArgument('$logger', new Reference(LoggerInterface::class));
        $definition->setArgument('$router', new Reference(RouterInterface::class));
        $definition->setArgument('$accessToken', $container->getParameter('docusign.access_token'));
        $definition->setArgument('$accountId', $container->getParameter('docusign.account_id'));
        $definition->setArgument('$defaultSignerName', $container->getParameter('docusign.default_signer_name'));
        $definition->setArgument('$defaultSignerEmail', $container->getParameter('docusign.default_signer_email'));
        $definition->setArgument('$apiURI', $container->getParameter('docusign.api_uri'));
        $definition->setArgument('$callBackRouteName', $container->getParameter('docusign.callback_route_name'));
        $definition->setArgument('$webhookRouteName', $container->getParameter('docusign.webhook_route_name'));
    }
}
