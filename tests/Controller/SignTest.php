<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\Sign;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\Exception\MissingMandatoryParameterHttpException;
use DocusignBundle\Utils\SignatureExtractor;
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
    public function testSign(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(PreSignEvent::class, Argument::type(PreSignEvent::class))->shouldBeCalled();

        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);
        $signatureExtractorProphecy->getSignatures()->willReturn([
            [
                'page' => 1,
                'xPosition' => 200,
                'yPosition' => 300,
            ]
        ]);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);
        $envelopeBuilderProphecy->createEnvelope()->willReturn('dummyURL');
        $envelopeBuilderProphecy->addSignatureZone(1, 200, 300)->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn('dummyPath');

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $response = (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertStringContainsString('dummyURL', $response->getContent());
    }

    public function testSignWithoutParam(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(PreSignEvent::class, Argument::type(PreSignEvent::class))->shouldNotBeCalled();

        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn(null);

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $this->expectException(MissingMandatoryParameterHttpException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
    }

    public function testSignFileNotFound(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(PreSignEvent::class, Argument::type(PreSignEvent::class))->shouldBeCalled();

        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);
        $signatureExtractorProphecy->getSignatures()->willReturn([
            [
                'page' => 1,
                'xPosition' => 200,
                'yPosition' => 300,
            ]
        ]);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->addSignatureZone(Argument::type('integer'), Argument::type('integer'), Argument::type('integer'))->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->createEnvelope()->willThrow(FileNotFoundException::class);

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn('dummyPath');

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->expectException(NotFoundHttpException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
    }

    public function testSignNoSignature(): void
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(PreSignEvent::class, Argument::type(PreSignEvent::class))->shouldBeCalled();

        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);
        $signatureExtractorProphecy->getSignatures()->willReturn([]);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->addSignatureZone()->shouldNotBeCalled();
        $envelopeBuilderProphecy->createEnvelope()->shouldNotBeCalled();

        $parameterBagProphecy = $this->prophesize(ParameterBag::class);
        $parameterBagProphecy->get('path')->willReturn('dummyPath');

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->query = $parameterBagProphecy->reveal();

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->expectException(\LogicException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $eventDispatcherProphecy->reveal(), $loggerProphecy->reveal());
    }
}
