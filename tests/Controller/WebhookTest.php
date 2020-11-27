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
use DocusignBundle\Tests\ProphecyTrait;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class WebhookTest extends TestCase
{
    use ProphecyTrait;

    private $requestProphecy;
    private $queryProphecy;
    private $eventDispatcherProphecy;
    private $loggerProphecy;
    private $tokenEncoderProphecy;

    protected function setUp(): void
    {
        $this->requestProphecy = $this->prophesize(Request::class);
        $this->requestProphecy->query = $this->queryProphecy = $this->prophesize(ParameterBag::class);
        $this->eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $this->loggerProphecy = $this->prophesize(LoggerInterface::class);
        $this->tokenEncoderProphecy = $this->prophesize(TokenEncoderInterface::class);
    }

    public function testTheWebhookControllerDispatchesAnEventWhenCalled(): void
    {
        $this->tokenEncoderProphecy->isTokenValid(['foo' => 'bar'], 'token')->willReturn(true)->shouldBeCalled();
        $this->queryProphecy->all()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $this->queryProphecy->get('_token')->willReturn('token')->shouldBeCalled();

        $this->requestProphecy->getContent()->willReturn(<<<XML
<?xml version="1.0" encoding="utf-8"?>
<DocuSignEnvelopeInformation xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.docusign.net/API/3.0">
  <EnvelopeStatus>
    <Status>Completed</Status>
  </EnvelopeStatus>
</DocuSignEnvelopeInformation>
XML
        )->shouldBeCalled();
        $this->loggerProphecy->info('DocuSign Webhook called.', ['status' => 'Completed'])->shouldBeCalled();

        $this->eventDispatcherProphecy->dispatch(Argument::type(CompletedEvent::class))->shouldBeCalled();

        $response = (new Webhook($this->tokenEncoderProphecy->reveal()))(
            $this->requestProphecy->reveal(),
            $this->eventDispatcherProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
        $this->assertStringContainsString('', $response->getContent());
    }

    public function testTheWebhookControllerIsForbiddenIsTokenIsInvalid(): void
    {
        $this->tokenEncoderProphecy->isTokenValid(['foo' => 'bar'], 'token')->willReturn(false)->shouldBeCalled();
        $this->queryProphecy->all()->willReturn(['foo' => 'bar'])->shouldBeCalled();
        $this->queryProphecy->get('_token')->willReturn('token')->shouldBeCalled();

        $this->requestProphecy->getContent()->shouldNotBeCalled();
        $this->loggerProphecy->info('DocuSign Webhook called.', ['status' => 'Completed'])->shouldNotBeCalled();
        $this->eventDispatcherProphecy->dispatch(CompletedEvent::class, Argument::type(CompletedEvent::class))->shouldNotBeCalled();

        $this->expectException(AccessDeniedHttpException::class);

        (new Webhook($this->tokenEncoderProphecy->reveal()))(
            $this->requestProphecy->reveal(),
            $this->eventDispatcherProphecy->reveal(),
            $this->loggerProphecy->reveal()
        );
    }
}
