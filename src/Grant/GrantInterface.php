<?php

declare(strict_types=1);

namespace DocusignBundle\Grant;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface GrantInterface
{
    public function __invoke(): string;
}
