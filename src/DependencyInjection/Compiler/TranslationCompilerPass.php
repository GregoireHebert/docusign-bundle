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
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Translation\TranslatorInterface;

final class TranslationCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $translatorAwareServices = $container->findTaggedServiceIds('docusign.translator.aware');

        foreach ($translatorAwareServices as $serviceId => $attributes) {
            $serviceDefinition = $container->findDefinition($serviceId);
            $serviceDefinition->addMethodCall('setTranslator', [new Reference(TranslatorInterface::class, ContainerInterface::IGNORE_ON_INVALID_REFERENCE)]);
        }
    }
}
