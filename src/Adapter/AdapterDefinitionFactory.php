<?php

declare(strict_types=1);

namespace DocusignBundle\Adapter;

use DocusignBundle\Adapter\Builder\AdapterDefinitionBuilderInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * flysystem compatibility.
 */
class AdapterDefinitionFactory
{
    /**
     * @var AdapterDefinitionBuilderInterface[]
     */
    private $builders;

    public function __construct()
    {
        $this->builders = [
            new Builder\LocalAdapterDefinitionBuilder(),
        ];
    }

    public function createDefinition(string $name, array $options): ?Definition
    {
        foreach ($this->builders as $builder) {
            if ($builder->getName() === $name) {
                return $builder->createDefinition($options);
            }
        }

        return null;
    }
}
