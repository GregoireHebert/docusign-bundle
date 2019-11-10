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

namespace DocusignBundle\Tests\Events;

use DocusignBundle\Events\AuthenticationFailedEvent;
use DocusignBundle\Events\AutoRespondedEvent;
use DocusignBundle\Events\CompletedEvent;
use DocusignBundle\Events\DeclinedEvent;
use DocusignBundle\Events\DeliveredEvent;
use DocusignBundle\Events\SentEvent;
use DocusignBundle\Events\WebhookEventFactory;
use DocusignBundle\Exception\InvalidStatusException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class WebhookEventFactoryTest extends TestCase
{
    /**
     * @dataProvider getEvents
     */
    public function testCreateEventFromStatus(string $eventClass, string $eventName): void
    {
        $this->assertInstanceOf($eventClass, WebhookEventFactory::create(
            $eventName,
            new \SimpleXMLElement(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<DocuSignEnvelopeInformation xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.docusign.net/API/3.0">
  <EnvelopeStatus>
    <Status>Completed</Status>
  </EnvelopeStatus>
</DocuSignEnvelopeInformation>
XML
            ),
            $this->prophesize(Request::class)->reveal()
        ));
    }

    public function getEvents(): array
    {
        return [
            [SentEvent::class, 'Sent'],
            [DeliveredEvent::class, 'Delivered'],
            [CompletedEvent::class, 'Completed'],
            [DeclinedEvent::class, 'Declined'],
            [AuthenticationFailedEvent::class, 'AuthenticationFailed'],
            [AutoRespondedEvent::class, 'AutoResponded'],
        ];
    }

    public function testCannotCreateEventFromInvalidStatus(): void
    {
        $this->expectException(InvalidStatusException::class);
        WebhookEventFactory::create('invalid');
    }
}
