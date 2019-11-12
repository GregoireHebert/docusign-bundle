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
