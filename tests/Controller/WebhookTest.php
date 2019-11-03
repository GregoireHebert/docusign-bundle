<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\Webhook;
use DocusignBundle\Events\DocumentSignedEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebhookTest extends TestCase
{
    public function testWebhook(): void
    {
        $requestProphecy = $this->prophesize(Request::class);

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(DocumentSignedEvent::class, Argument::type(DocumentSignedEvent::class))->shouldBeCalled();

        $response = (new Webhook())($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertStringContainsString('', $response->getContent());
    }
}
