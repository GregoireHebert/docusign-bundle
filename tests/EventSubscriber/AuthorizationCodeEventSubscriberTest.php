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

namespace DocusignBundle\Tests\EventSubscriber;

use DocusignBundle\AuthorizationCodeHandler\AuthorizationCodeHandlerInterface;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Events\AuthorizationCodeEvent;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\EventSubscriber\AuthorizationCodeEventSubscriber;
use DocusignBundle\TokenEncoder\TokenEncoderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class AuthorizationCodeEventSubscriberTest extends TestCase
{
    /**
     * @var ObjectProphecy|AuthorizationCodeHandlerInterface
     */
    private $authorizationCodeHandlerProphecy;

    /**
     * @var ObjectProphecy|TokenEncoderInterface
     */
    private $tokenEncoderProphecy;

    /**
     * @var ObjectProphecy|RouterInterface
     */
    private $routerProphecy;

    /**
     * @var ObjectProphecy|HttpClientInterface
     */
    private $clientProphecy;

    private $eventSubscriber;

    protected function setUp(): void
    {
        $this->authorizationCodeHandlerProphecy = $this->prophesize(AuthorizationCodeHandlerInterface::class);
        $this->tokenEncoderProphecy = $this->prophesize(TokenEncoderInterface::class);
        $this->routerProphecy = $this->prophesize(RouterInterface::class);
        $this->clientProphecy = $this->prophesize(HttpClientInterface::class);

        $this->eventSubscriber = new AuthorizationCodeEventSubscriber(
            $this->authorizationCodeHandlerProphecy->reveal(),
            $this->tokenEncoderProphecy->reveal(),
            $this->routerProphecy->reveal(),
            'db503811-1f7e-47f5-a6c3-7a471ba0394b',
            'bfa723bd-3650-40a3-a468-a09da3e8d4b3',
            true,
            $this->clientProphecy->reveal()
        );
    }

    public function testItRedirectsTheUserToDocuSignOnPreSign(): void
    {
        $eventProphecy = $this->prophesize(PreSignEvent::class);
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);

        $eventProphecy->getEnvelopeBuilder()->willReturn($envelopeBuilderProphecy)->shouldBeCalled();
        $envelopeBuilderProphecy->getAuthMode()->willReturn(EnvelopeBuilder::AUTH_MODE_CODE)->shouldBeCalled();
        $envelopeBuilderProphecy->getName()->willReturn('default')->shouldBeCalled();

        $this->tokenEncoderProphecy->encode([])->willReturn('token')->shouldBeCalled();
        $eventProphecy->setResponse(Argument::type(RedirectResponse::class))->shouldBeCalled();

        $this->eventSubscriber->onPreSign($eventProphecy->reveal());
    }

    public function testItGeneratesAnAccessTokenOnAuthorizationCode(): void
    {
        $eventProphecy = $this->prophesize(AuthorizationCodeEvent::class);
        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $requestProphecy = $this->prophesize(Request::class);
        $queryProphecy = $requestProphecy->query = $this->prophesize(ParameterBag::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);

        $eventProphecy->getRequest()->willReturn($requestProphecy)->shouldBeCalled();
        $queryProphecy->get('code')->willReturn('azerty')->shouldBeCalled();
        $this->clientProphecy->request('POST', AuthorizationCodeEventSubscriber::DEMO_ACCOUNT_API_URI.'/oauth/token', [
            'headers' => [
                'Authorization' => 'Basic ZGI1MDM4MTEtMWY3ZS00N2Y1LWE2YzMtN2E0NzFiYTAzOTRiOmJmYTcyM2JkLTM2NTAtNDBhMy1hNDY4LWEwOWRhM2U4ZDRiMw==',
            ],
            'body' => 'grant_type=authorization_code&code=azerty',
        ])->willReturn($responseProphecy)->shouldBeCalled();

        $responseProphecy->toArray()->willReturn(['access_token' => 'token'])->shouldBeCalled();
        $this->authorizationCodeHandlerProphecy->write('token', ['signature_name' => 'default'])->shouldBeCalled();
        $eventProphecy->getEnvelopeBuilder()->willReturn($envelopeBuilderProphecy)->shouldBeCalled();
        $envelopeBuilderProphecy->getName()->willReturn('default')->shouldBeCalled();

        $this->eventSubscriber->onAuthorizationCode($eventProphecy->reveal());
    }
}
