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

use DocusignBundle\Controller\Callback;
use DocusignBundle\Events\DocumentSignatureCompletedEvent;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CallbackTest extends TestCase
{
    public function testCallback(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('event')->willReturn(Callback::EVENT_COMPLETE);
        $requestProphecy->get('envelopeId')->willReturn('dummyEnvelopeId');

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(DocumentSignatureCompletedEvent::class, Argument::type(DocumentSignatureCompletedEvent::class))->shouldBeCalled();

        $response = (new Callback())($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
