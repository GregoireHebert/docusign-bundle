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

namespace DocusignBundle;

use DocusignBundle\DependencyInjection\Compiler\PluginPass;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DocusignBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new PluginPass());

        if (!class_exists(FlysystemBundle::class)) {
            $container->addCompilerPass(new PluginPass());
        }
    }
}
