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
 *
 * @group functional
 */
final class ClickwrapTest extends PantherTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        static::createPantherClient()->request('GET', '/logout');
    }

    public function testTheClickwrapRequiresAnAuthentication(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/clickwrap');

        $this->assertSame('Log in!', $client->getTitle());
        $this->assertCount(1, $crawler->filter('form'));
    }

    public function testItDisplaysAClickwrapDocument(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/clickwrap');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $crawler = $client->waitFor('#container #content iframe');

        $this->assertSame('Clickwrap', $client->getTitle());

        // If the iframe is found, the test is ok. The rest is done on DocuSign
        $this->assertCount(1, $crawler->filter('#container #content iframe'));
    }
}
