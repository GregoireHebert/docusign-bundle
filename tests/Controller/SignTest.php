<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\Controller\Sign;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Utils\SignatureExtractor;
use League\Flysystem\FileNotFoundException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\MissingMandatoryParametersException;

class SignTest extends TestCase
{
    public function testSign(): void
    {
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

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn('dummyPath');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $response = (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $loggerProphecy->reveal());
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertStringContainsString('dummyURL', $response->getContent());
    }

    public function testSignWithoutParam(): void
    {
        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn(null);

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldNotBeCalled();

        $this->expectException(MissingMandatoryParametersException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $loggerProphecy->reveal());
    }

    public function testSignFileNotFound(): void
    {
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

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn('dummyPath');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $loggerProphecy->notice(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->expectException(NotFoundHttpException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $loggerProphecy->reveal());
    }

    public function testSignNoSignature(): void
    {
        $signatureExtractorProphecy = $this->prophesize(SignatureExtractor::class);
        $signatureExtractorProphecy->getSignatures()->willReturn([]);

        $envelopeBuilderProphecy = $this->prophesize(EnvelopeBuilder::class);
        $envelopeBuilderProphecy->setFile('dummyPath')->willReturn($envelopeBuilderProphecy->reveal());
        $envelopeBuilderProphecy->addSignatureZone()->shouldNotBeCalled();
        $envelopeBuilderProphecy->createEnvelope()->shouldNotBeCalled();

        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('path')->willReturn('dummyPath');

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $this->expectException(\LogicException::class);
        (new Sign())($envelopeBuilderProphecy->reveal(), $signatureExtractorProphecy->reveal(), $requestProphecy->reveal(), $loggerProphecy->reveal());
    }
}
