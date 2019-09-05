<?php

declare(strict_types=1);

namespace DocusignBundle\Adapter\Builder;

use Symfony\Component\DependencyInjection\Definition;

/**
 * flysystem compatibility.
 */
interface AdapterDefinitionBuilderInterface
{
    public function getName(): string;

    /**
     * Create the definition for this builder's adapter given an array of options.
     */
    public function createDefinition(array $options): Definition;
}
