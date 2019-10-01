<?php

declare(strict_types=1);

namespace DocusignBundle;

use DocusignBundle\DependencyInjection\Compiler\EnvelopeBuilderPass;
use DocusignBundle\DependencyInjection\Compiler\PluginPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DocusignBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new EnvelopeBuilderPass());
        $container->addCompilerPass(new PluginPass());
    }
}
