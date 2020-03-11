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
final class EmbeddedAuthCodeTest extends PantherTestCase
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

    public function testTheEmbeddedDocumentsListRequiresAnAuthentication(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/embedded_auth_code');

        $this->assertSame('Log in!', $client->getTitle());
        $this->assertCount(1, $crawler->filter('form'));
    }

    public function testItDisplaysAListOfEmbeddedDocuments(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/embedded_auth_code');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $crawler = $client->waitFor('#container #content ul li');

        $this->assertSame('Documents list (embedded mode - AuthorizationCode auth)', $client->getTitle());
        $this->assertCount(1, $crawler->filter('#container #content ul li'));
        $this->assertSame('dummy.pdf', $crawler->filter('#container #content ul li a')->first()->text());
    }

    public function testICanSignAnEmbeddedDocument(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/embedded_auth_code');
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
        $crawler = $client->waitFor('#action-bar-btn-continue');

        if ($crawler->filter('#disclosureAccepted')->isDisplayed()) {
            $crawler->filter('label[for=disclosureAccepted]')->click();
        }

        $crawler->filter('#action-bar-btn-continue')->click();
        $crawler = $client->waitFor('.page-tabs .signature-tab > button');

        // Wait for "Comment tooltip" button (optional use-case)
        sleep(1);
        if ($crawler->filter('#comments-tooltip-btn-ok')->isDisplayed()) {
            $crawler->filter('#comments-tooltip-btn-ok')->click();
        }

        $crawler->filter('.page-tabs .signature-tab > button')->click();
        $crawler = $client->waitFor('#action-bar-btn-finish');

        // Wait for "Adopt and Sign" button (optional use-case)
        sleep(1);
        if ($crawler->selectButton('Adopt and Sign')->count()) {
            $crawler->selectButton('Adopt and Sign')->click();
        }

        $crawler->filter('#action-bar-btn-finish')->click();
        $crawler = $client->waitFor('.alert');

        $this->assertSame('The document has been successfully signed!', $crawler->filter('.alert')->text());
        $this->assertSame('/embedded_auth_code', parse_url($crawler->getUri(), PHP_URL_PATH));
    }
}
