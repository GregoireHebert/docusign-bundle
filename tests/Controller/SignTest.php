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

use DocusignBundle\Controller\Sign;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use DocusignBundle\Tests\ProphecyTrait;
use League\Flysystem\FileNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SignTest extends TestCase
{
    use ProphecyTrait;

    public function testTheSignControllerRedirectsTheUserToDocusign(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(PreSignEvent::class))->shouldBeCalled();

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $envelopeBuilderProphecy->createEnvelope()->willReturn('dummyURL');
        $envelopeBuilderProphecy->addSignatureZone(1, 200, 300)->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn('dummyPath');
        $parameterBagProphecy->get('signerName')->willReturn('John DOE');
        $parameterBagProphecy->get('signerEmail')->willReturn('john.doe@example.com');

        $envelopeBuilderProphecy->setSignerName('John DOE');
        $envelopeBuilderProphecy->setSignerEmail('john.doe@example.com');

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $response = (new Sign($envelopeBuilderProphecy->reveal()))($requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertStringContainsString('dummyURL', $response->getContent());
    }

    public function testTheSignControllerCalledWithoutParamThrowsAnError(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(PreSignEvent::class))->shouldNotBeCalled();

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn(null);
        $parameterBagProphecy->get('signerName')->shouldNotBeCalled();
        $parameterBagProphecy->get('signerEmail')->shouldNotBeCalled();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $this->expectException(MissingMandatoryParameterHttpException::class);
        (new Sign($envelopeBuilderProphecy->reveal()))($requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
    }

    public function testTheSignControllerCalledWithWrongFilePathThrowAnError(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::type(PreSignEvent::class))->shouldBeCalled();

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilderInterface::class);
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->addSignatureZone(Argument::type('integer'), Argument::type('integer'), Argument::type('integer'))->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->createEnvelope()->willThrow(FileNotFoundException::class);

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn('dummyPath');
        $parameterBagProphecy->get('signerName')->willReturn(null);
        $parameterBagProphecy->get('signerEmail')->willReturn(null);

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->error(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->expectException(NotFoundHttpException::class);
        (new Sign($envelopeBuilderProphecy->reveal()))($requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
    }
}
