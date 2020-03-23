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
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ConsentTest extends TestCase
{
    public function testItRedirectsToValidUri(): void
    {
        /** @var Request|ObjectProphecy $requestMock */
        $requestMock = $this->prophesize(Request::class);
        $requestMock->getSchemeAndHttpHost()->willReturn('https://www.example.com')->shouldBeCalled();

        $consent = new Consent(
            true,
            'c3b2d475-2cbd-47f5-a903-9b3aa0fefe5b'
        );
        $response = $consent($requestMock->reveal());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(Consent::DEMO_CONSENT_URI.'?response_type=code&scope=signature%20impersonation&client_id=c3b2d475-2cbd-47f5-a903-9b3aa0fefe5b&redirect_uri=https://www.example.com', $response->getTargetUrl());
    }
}
