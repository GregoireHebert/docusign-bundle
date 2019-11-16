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
final class EmbeddedTest extends PantherTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();
        static::createPantherClient()->request('GET', '/logout');
    }

//    public function testAcceptConsent(): void
//    {
//        $client = static::createPantherClient();
//
//        $crawler = $client->request('GET', '/docusign/consent/default');
//        if ('DocuSign' === $crawler->filter('head > title')->first()->text()) {
//            $client->submitForm('Accept');
//        }
//        $client->takeScreenshot('consent.png');
//        // todo Missing assertion
//    }

    public function testTheEmbeddedDocumentsListRequiresAnAuthentication(): void
    {
        $client = static::createPantherClient();

        $crawler = $client->request('GET', '/embedded');

        $this->assertPageTitleSame('Log in!');
        $this->assertCount(1, $crawler->filter('form'));
    }

    public function testItDisplaysAListOfEmbeddedDocuments(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/embedded');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $crawler = $client->waitFor('#container #content ul li');

        $this->assertPageTitleSame('Embedded documents');
        $this->assertCount(1, $crawler->filter('#container #content ul li'));
        $this->assertSame('dummy.pdf', $crawler->filter('#container #content ul li a')->first()->text());
    }

    public function testICanSignAnEmbeddedDocument(): void
    {
        $client = static::createPantherClient();

        $client->request('GET', '/embedded');
        $client->submitForm('Login', [
            '_username' => 'admin',
            '_password' => '4dm1n',
        ]);
        $client->waitFor('#container #content ul li');

        $client->clickLink('dummy.pdf');
        $client->takeScreenshot('build/logs/phpunit/waiting.png');
        $crawler = $client->waitFor('#action-bar-btn-continue');
        $client->takeScreenshot('build/logs/phpunit/failing.png');

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
        $client->takeScreenshot('build/logs/phpunit/alert.png');
        $crawler = $client->waitFor('.alert');

        $this->assertSame('The document has been successfully signed!', $crawler->filter('.alert')->text());
        $this->assertSame('/embedded', parse_url($crawler->getUri(), PHP_URL_PATH));
    }
}
