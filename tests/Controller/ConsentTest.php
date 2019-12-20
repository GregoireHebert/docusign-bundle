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

use DocusignBundle\Controller\Consent;
use DocusignBundle\EnvelopeBuilderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ConsentTest extends TestCase
{
    /**
     * @dataProvider getData
     */
    public function testItRedirectsToValidUri(string $responseType, string $expected): void
    {
        /** @var EnvelopeBuilderInterface|ObjectProphecy $envelopeBuilderMock */
        $envelopeBuilderMock = $this->prophesize(EnvelopeBuilderInterface::class);
        /** @var RouterInterface|ObjectProphecy $routerMock */
        $routerMock = $this->prophesize(RouterInterface::class);

        $envelopeBuilderMock->getEnvelopeId()->willReturn('12345')->shouldBeCalledTimes(1);
        $envelopeBuilderMock->getCallbackParameters()->willReturn(['foo' => 'bar'])->shouldBeCalledTimes(1);
        $envelopeBuilderMock->getCallback()->willReturn('docusign_callback')->shouldBeCalledTimes(1);
        $routerMock
            ->generate('docusign_callback', ['envelopeId' => '12345', 'foo' => 'bar'], Router::ABSOLUTE_URL)
            ->willReturn('https://www.example.com/docusign/callback')
            ->shouldBeCalledTimes(1);

        $consent = new Consent(
            $envelopeBuilderMock->reveal(),
            $routerMock->reveal(),
            Consent::DEMO_CONSENT_URI,
            'c3b2d475-2cbd-47f5-a903-9b3aa0fefe5b',
            $responseType
        );
        $response = $consent();
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals($expected, $response->getTargetUrl());
    }

    public function getData(): array
    {
        return [
            ['code', Consent::DEMO_CONSENT_URI.'?response_type=code&scope=signature%20impersonation&client_id=c3b2d475-2cbd-47f5-a903-9b3aa0fefe5b&redirect_uri=https://www.example.com/docusign/callback'],
            ['token', Consent::DEMO_CONSENT_URI.'?response_type=token&scope=signature%20impersonation&client_id=c3b2d475-2cbd-47f5-a903-9b3aa0fefe5b&redirect_uri=https://www.example.com/docusign/callback'],
        ];
    }
}
