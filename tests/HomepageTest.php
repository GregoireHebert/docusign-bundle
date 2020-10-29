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

namespace DocusignBundle\Tests;

use Symfony\Component\Panther\PantherTestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class HomepageTest extends PantherTestCase
{
    public function testItDisplaysTheHomepage(): void
    {
        $client = static::createPantherClient();
        $client->request('GET', '/');

        $this->assertSame('Welcome to the DocuSign', $client->getTitle());
    }
}
