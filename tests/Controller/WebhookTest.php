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

namespace DocusignBundle\Tests\Controller;

use DocusignBundle\Controller\Webhook;
use DocusignBundle\Events\CompletedEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookTest extends TestCase
{
    public function testWebhook(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $requestProphecy->getContent()->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<DocuSignEnvelopeInformation xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.docusign.net/API/3.0">
  <EnvelopeStatus>
    <Status>Completed</Status>
  </EnvelopeStatus>
</DocuSignEnvelopeInformation>
XML
        )->shouldBeCalled();
        $loggerProphecy->info('DocuSign Webhook called.', ['status' => 'Completed'])->shouldBeCalled();

        $eventDispatcherProphecy->dispatch(CompletedEvent::class, Argument::type(CompletedEvent::class))->shouldBeCalled();

        $response = (new Webhook())($requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertStringContainsString('', $response->getContent());
    }
}
