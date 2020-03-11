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
final class RemoteAuthCodeTest extends PantherTestCase
{
    private static $docusignEmail;
    private static $docusignPassword;

    public static function setUpBeforeClass(): void
    {
        static::bootKernel();
        self::$docusignEmail = self::$container->getParameter('docusign.email');
        self::$docusignPassword = self::$container->getParameter('docusign.password');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::createPantherClient()->request('GET', '/logout');
    }

    public function testTheRemoteDocumentsListRequiresAnAuthentication(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/remote_auth_code');

        $this->assertSame('Log in!', $client->getTitle());
        $this->assertCount(1, $crawler->filter('form'));
    }

    public function testItDisplaysAListOfRemoteDocuments(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/remote_auth_code');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $crawler = $client->waitFor('#container #content ul li');

        $this->assertSame('Documents list (remote mode - AuthorizationCode auth)', $client->getTitle());
        $this->assertCount(1, $crawler->filter('#container #content ul li'));
        $this->assertSame('dummy.pdf', $crawler->filter('#container #content ul li a')->first()->text());
    }

    public function testICanSentARemoteDocumentToBeSigned(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/remote_auth_code');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $client->waitFor('#container #content ul li');

        $crawler = $client->clickLink('dummy.pdf');
        $submit = $crawler->filter('button[data-qa=submit-username]');
        if (0 < $submit->count()) {
            $form = $submit->form([
                'email' => self::$docusignEmail,
            ]);
            $crawler = $client->submit($form);
            $form = $crawler->filter('button[data-qa=submit-password]')->form([
                'password' => self::$docusignPassword,
            ]);
            $client->submit($form);
        }

        $crawler = $client->waitFor('.alert');
        $this->assertSame('Authorization has been granted! You can now sign the document.', $crawler->filter('.alert')->text());
        $client->clickLink('dummy.pdf');
        $crawler = $client->waitFor('.alert');

        $this->assertSame('The document has been successfully sent to the signer!', $crawler->filter('.alert')->text());
        $this->assertSame('/remote_auth_code', parse_url($crawler->getUri(), PHP_URL_PATH));
    }
}
