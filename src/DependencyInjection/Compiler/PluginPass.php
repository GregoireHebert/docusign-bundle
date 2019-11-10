<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
