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
final class RemoteTest extends PantherTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        static::createPantherClient()->request('GET', '/logout');
    }

    public function testTheRemoteDocumentsListRequiresAnAuthentication(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/remote');

        $this->assertSame('Log in!', $client->getTitle());
        $this->assertCount(1, $crawler->filter('form'));
    }

    public function testItDisplaysAListOfRemoteDocuments(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/remote');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $crawler = $client->waitFor('#container #content ul li');

        $this->assertSame('Documents list (remote mode - JWT auth)', $client->getTitle());
        $this->assertCount(1, $crawler->filter('#container #content ul li'));
        $this->assertSame('dummy.pdf', $crawler->filter('#container #content ul li a')->first()->text());
    }

    public function testICanSentARemoteDocumentToBeSigned(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/remote');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $client->waitFor('#container #content ul li');

        $client->clickLink('dummy.pdf');
        $crawler = $client->waitFor('.alert');

        $this->assertSame('The document has been successfully sent to the signer!', $crawler->filter('.alert')->text());
        $this->assertSame('/remote', parse_url($crawler->getUri(), \PHP_URL_PATH));
    }
}
