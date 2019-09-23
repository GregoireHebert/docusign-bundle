<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection\Compiler;

use DocusignBundle\Utils\SignatureExtractor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SignatureExtractorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->findDefinition(SignatureExtractor::class);
        $definition->addMethodCall('setSignaturesOverridable', [$container->getParameter('docusign.signatures_overridable')]);
        $definition->addMethodCall('setDefaultSignatures', [$container->getParameter('docusign.signatures')]);
    }
}
