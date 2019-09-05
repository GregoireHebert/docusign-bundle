<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\Sign;
use DocusignBundle\EnvelopeBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SignTest extends TestCase
{
    public function testSign(): void
    {
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);
        $envelopeBuilderProphecy->createEnvelope()->willReturn('dummyURL');
        $envelopeBuilderProphecy->addSignatureZone(Argument::type('integer'), Argument::type('integer'), Argument::type('integer'))->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn('dummyPath');

        $response = (new Sign())($envelopeBuilderProphecy->reveal(), $requestProphecy->reveal());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertStringContainsString('dummyURL', $response->getContent());
    }

    public function testSignWithoutParam(): void
    {
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $requestProphecy->reveal());
    }
}
