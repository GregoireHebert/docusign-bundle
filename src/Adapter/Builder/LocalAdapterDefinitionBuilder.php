<?php

declare(strict_types=1);

namespace DocusignBundle\Adapter\Builder;

use League\Flysystem\Adapter\Local;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * flysystem compatibility.
 */
class LocalAdapterDefinitionBuilder extends AbstractAdapterDefinitionBuilder
{
    public function getName(): string
    {
        return 'local';
    }

    protected function getRequiredPackages(): array
    {
        return [];
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('directory');
        $resolver->setAllowedTypes('directory', 'string');

        $resolver->setDefault('lock', 0);
        $resolver->setAllowedTypes('lock', 'scalar');

        $resolver->setDefault('skip_links', false);
        $resolver->setAllowedTypes('skip_links', 'scalar');

        $resolver->setDefault('permissions', [
            'file' => [
                'public' => 0644,
                'private' => 0600,
            ],
            'dir' => [
                'public' => 0755,
                'private' => 0700,
            ],
        ]);
    }

    protected function configureDefinition(Definition $definition, array $options): void
    {
        $definition->setClass(Local::class);
        $definition->setArgument(0, $options['directory']);
        $definition->setArgument(1, $options['lock']);
        $definition->setArgument(2, $options['skip_links'] ? Local::SKIP_LINKS : Local::DISALLOW_LINKS);
        $definition->setArgument(3, $options['permissions']);
    }
}
