<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/*
 * FlySystem symfony 3.4 compatibility
 */
final class PluginPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $plugins = array_map(function ($id) {
            return new Reference($id);
        }, array_keys($container->findTaggedServiceIds('flysystem.plugin')));

        if (0 === \count($plugins)) {
            return;
        }

        /** @var Definition[] $storages */
        $storages = array_map(function ($id) use ($container) {
            return $container->findDefinition($id);
        }, array_keys($container->findTaggedServiceIds('flysystem.storage')));

        foreach ($storages as $storage) {
            foreach ($plugins as $plugin) {
                $storage->addMethodCall('addPlugin', [$plugin]);
            }
        }
    }
}
