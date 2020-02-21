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

namespace DocusignBundle\TokenEncoder;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
interface TokenEncoderInterface
{
    public function encode(array $parameters): string;

    public function isTokenValid(array $parameters, ?string $token): bool;
}
