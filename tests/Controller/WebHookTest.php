<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\WebHook;
use DocusignBundle\Events\DocumentSigned;
use DocusignBundle\Events\WebHookEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class WebHookTest extends TestCase
{
    public function testWebHook(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->getContent()->shouldBeCalled();

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(WebHookEvent::DOCUMENT_SIGNED, Argument::type(DocumentSigned::class))->shouldBeCalled();

        $response = (new WebHook())($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertStringContainsString('', $response->getContent());
    }
}
