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
    private $tokenEncoder;

    protected function setUp(): void
    {
        $this->tokenEncoder = new TokenEncoder('foo', 'bar');
    }

    public function testItEncodesTheTokenFromParameters(): void
    {
        $this->assertEquals('c51f94d8a9aa9226fe03661c74aaf3641cd101dc811b349a3852b7ecbfd94ef6', $this->tokenEncoder->encode([
            'foo' => 'bar',
            'lorem' => 'ipsum',
        ]));
    }

    public function testItChecksIfTheTokenIsValid(): void
    {
        $this->assertTrue($this->tokenEncoder->isTokenValid([
            'foo' => 'bar',
            'lorem' => 'ipsum',
        ], 'c51f94d8a9aa9226fe03661c74aaf3641cd101dc811b349a3852b7ecbfd94ef6'));
        $this->assertFalse($this->tokenEncoder->isTokenValid([
            'lorem' => 'ipsum',
            'foo' => 'bar',
        ], 'c51f94d8a9aa9226fe03661c74aaf3641cd101dc811b349a3852b7ecbfd94ef6'));
    }
}
