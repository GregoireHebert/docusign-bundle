<?php

declare(strict_types=1);

namespace DocusignBundle;

use DocusignBundle\DependencyInjection\Compiler\DocusignStoragePass;
use DocusignBundle\DependencyInjection\Compiler\EnvelopeBuilderPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DocusignBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new EnvelopeBuilderPass());
        $container->addCompilerPass(new DocusignStoragePass());
    }
}
