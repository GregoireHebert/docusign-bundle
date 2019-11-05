<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/*
 * FlySystem symfony 3.4 compatibility
 */
final class PluginPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public function process(ContainerBuilder $container): void
    {
        $plugins = $this->findAndSortTaggedServices('flysystem.plugin', $container);
        $storages = $container->findTaggedServiceIds('flysystem.storage');

        if (0 === \count($plugins) || 0 === \count($storages)) {
            return;
        }

        foreach ($storages as $storageId => $attributes) {
            $storageDefinition = $container->findDefinition($storageId);
            foreach ($plugins as $pluginId => $plugin) {
                $storageDefinition->addMethodCall('addPlugin', [$pluginId]);
            }
        }
    }
}
