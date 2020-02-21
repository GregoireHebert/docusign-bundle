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

namespace DocusignBundle\Tests\TokenEncoder;

use DocusignBundle\TokenEncoder\TokenEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class TokenEncoderTest extends TestCase
{
    public function testItChecksIfTheTokenIsValid(): void
    {
        $parameters = [
            'foo' => 'bar',
            'lorem' => 'ipsum',
        ];

        $tokenEncoder = new TokenEncoder('foo', 'bar');

        $this->assertTrue($tokenEncoder->isTokenValid($parameters, $tokenEncoder->encode($parameters)));
    }
}
