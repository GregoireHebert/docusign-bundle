<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use DocusignBundle\Bridge\FlySystem\Adapter\SharePointAdapter;
use DocusignBundle\Bridge\FlySystem\DocusignStorage;
use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

final class DocusignStoragePass implements CompilerPassInterface
{
    public const DOCUSIGN_STORAGE_SERVICE_ALIAS = 'docusign.storage';

    public function process(ContainerBuilder $container): void
    {
        if (false === $container->has(self::DOCUSIGN_STORAGE_SERVICE_ALIAS)) {
            $adapter = new Definition(SharePointAdapter::class);
            $adapter->setAutowired(true);
            $adapter->setAutoconfigured(true);
            $adapter->setPublic(false);

            $container->setDefinition(SharePointAdapter::class, $adapter);

            $storage = new Definition(DocusignStorage::class);
            $storage->setArgument('$adapter', new Reference(SharePointAdapter::class));
            $storage->setPublic(false);
            $storage->setAutoconfigured(true);

            $container->setDefinition(DocusignStorage::class, $storage);
            $container->setAlias(self::DOCUSIGN_STORAGE_SERVICE_ALIAS, DocusignStorage::class);
        }

        $eSignatureBuilderDefinition = $container->findDefinition(EnvelopeBuilder::class);
        $eSignatureBuilderDefinition->setArgument('$docusignStorage', new Reference(self::DOCUSIGN_STORAGE_SERVICE_ALIAS));
    }
}
