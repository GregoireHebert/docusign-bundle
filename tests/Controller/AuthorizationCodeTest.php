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

use DocusignBundle\Controller\AuthorizationCode;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Events\AuthorizationCodeEvent;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use DocusignBundle\Tests\ProphecyTrait;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthorizationCodeTest extends TestCase
{
    use ProphecyTrait;

    public function testTheAuthorizationCodeControllerIsValid(): void
    {
        $tokenEncoderProphecy = $this->prophesize(TokenEncoderInterface::class);
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $requestProphecy = $this->prophesize(Request::class);
        $queryProphecy = $requestProphecy->query = $this->prophesize(ParameterBag::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);

        $queryProphecy->get('state')->willReturn('foo')->shouldBeCalled();
        $tokenEncoderProphecy->isTokenValid([], 'foo')->willReturn(true)->shouldBeCalled();

        $queryProphecy->get('code')->willReturn('azerty')->shouldBeCalled();
        $eventDispatcherProphecy->dispatch(Argument::type(AuthorizationCodeEvent::class))->shouldBeCalled();

        $controller = new AuthorizationCode($tokenEncoderProphecy->reveal(), $envelopeBuilderProphecy->reveal());

        $response = $controller($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
    }

    public function testTheAuthorizationCodeControllerThrowsAnErrorIfTheSecurityFails(): void
    {
        $tokenEncoderProphecy = $this->prophesize(TokenEncoderInterface::class);
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $requestProphecy = $this->prophesize(Request::class);
        $queryProphecy = $requestProphecy->query = $this->prophesize(ParameterBag::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);

        $queryProphecy->get('state')->willReturn('foo')->shouldBeCalled();
        $tokenEncoderProphecy->isTokenValid([], 'foo')->willReturn(false)->shouldBeCalled();
        $this->expectException(AccessDeniedHttpException::class);

        $queryProphecy->get('code')->willReturn('azerty')->shouldNotBeCalled();

        $controller = new AuthorizationCode($tokenEncoderProphecy->reveal(), $envelopeBuilderProphecy->reveal());

        $response = $controller($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
    }

    public function testTheAuthorizationCodeControllerThrowsAnErrorIfTheCodeDoesNotExist(): void
    {
        $tokenEncoderProphecy = $this->prophesize(TokenEncoderInterface::class);
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $requestProphecy = $this->prophesize(Request::class);
        $queryProphecy = $requestProphecy->query = $this->prophesize(ParameterBag::class);
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);

        $queryProphecy->get('state')->willReturn('foo')->shouldBeCalled();
        $tokenEncoderProphecy->isTokenValid([], 'foo')->willReturn(true)->shouldBeCalled();

        $queryProphecy->get('code')->willReturn(null)->shouldBeCalled();
        $this->expectException(MissingMandatoryParameterHttpException::class);

        $controller = new AuthorizationCode($tokenEncoderProphecy->reveal(), $envelopeBuilderProphecy->reveal());

        $response = $controller($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(202, $response->getStatusCode());
    }
}
