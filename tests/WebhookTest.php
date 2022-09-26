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

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class WebhookTest extends WebTestCase
{
    public function testItCallsWebhookForCompletedEvent(): void
    {
        $client = static::createClient();
        $parameters = [
            'lorem' => 'ipsum',
            'foo' => 'bar',
        ];
        $client->request('POST', '/docusign/webhook/default?'.http_build_query($parameters + [
            '_token' => $client->getContainer()->get('test.docusign.token_encoder.default')->encode($parameters),
        ]
        ), [], [], [
            'CONTENT_TYPE' => 'text/xml; charset=utf-8',
            'HTTPS' => true,
        ], file_get_contents(__DIR__.'/xml/completed.xml'));

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
        $this->assertFileExists('completed.pdf');
    }
}
