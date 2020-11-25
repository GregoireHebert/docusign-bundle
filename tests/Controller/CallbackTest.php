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
use DocusignBundle\DocusignBundle;
use DocusignBundle\Events\DocumentSignatureCompletedEvent;
use DocusignBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class CallbackTest extends TestCase
{
    use ProphecyTrait;

    public function testTheCallbackControllerIsValid(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('event')->willReturn(Callback::EVENT_COMPLETE);
        $requestProphecy->get('envelopeId')->willReturn('dummyEnvelopeId');

        $translatorProphecy = $this->prophesize(TranslatorInterface::class);
        $translatorProphecy->trans(Argument::type('string'), [], DocusignBundle::TRANSLATION_DOMAIN)->shouldBeCalled()->willReturn('string');

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(DocumentSignatureCompletedEvent::class))->shouldBeCalled();

        $callback = new Callback();
        $callback->setTranslator($translatorProphecy->reveal());

        $response = $callback($requestProphecy->reveal(), $eventDispatcherProphecy->reveal());
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
