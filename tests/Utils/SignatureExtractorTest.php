<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\Exception\AmbiguousDocumentSelectionException;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SignatureExtractorTest extends TestCase
{
    public function testDefaultSignaturesWithType(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn(null);
        $requestProphecy->get('documentType')->willReturn('Quote');

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setDefaultSignatures([
            'Quote' => [
                'signatures' => [
                    ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
                    ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
                ]
            ],
            'Receipt' => [
                'signatures' => [
                    ['page' => 1, 'xPosition' => 250, 'yPosition' => 50],
                ]
            ]
        ]);

        $this->assertEquals([
            ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
            ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testNoSignatures(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn(null);
        $requestProphecy->get('documentType')->willReturn(null);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setDefaultSignatures([]);

        $this->assertNull($signatureExtractor->getSignatures());
    }

    public function testDefaultSignaturesWithAmbiguity(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn(null);
        $requestProphecy->get('documentType')->willReturn(null);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setDefaultSignatures([
            'Quote' => [
                'signatures' => [
                    ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
                    ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
                ]
            ],
            'Receipt' => [
                'signatures' => [
                    ['page' => 1, 'xPosition' => 250, 'yPosition' => 50],
                ]
            ]
        ]);

        $this->expectException(AmbiguousDocumentSelectionException::class);
        $signatureExtractor->getSignatures();
    }

    public function testDefaultSignaturesNoAmbiguity(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn(null);
        $requestProphecy->get('documentType')->willReturn(null);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setDefaultSignatures([
            'Quote' => [
                'signatures' => [
                    ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
                    ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
                ]
            ]
        ]);

        $this->assertEquals([
            ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
            ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testRequestSignatureInvalid(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn('invalid');

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter `signatures` must be an array of signatures, with the `page` (optional, default is 1), the `xPosition` and the `yPosition` values.');
        $signatureExtractor->getSignatures();
    }

    public function testDefaultSignatureCannotBeOverridden(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('documentType')->willReturn(null);
        $requestProphecy->get('signatures')->shouldNotBeCalled();

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setDefaultSignatures([]);

        $this->assertNull($signatureExtractor->getSignatures());
    }

    public function testRequestSignatureGoodResolution(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
            ['xPosition' => 600, 'yPosition' => 100],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->assertEquals([
            ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
            ['page' => 1, 'xPosition' => 600, 'yPosition' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testRequestSignatureMissingXPosition(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 3,'yPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "xPosition" is missing.');
        $signatureExtractor->getSignatures();
    }

    public function testRequestSignatureMissingYPosition(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 3,'xPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "yPosition" is missing.');
        $signatureExtractor->getSignatures();
    }

    public function testRequestSignatureXPositionWrongType(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 3,'xPosition' => 'wrong', 'yPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "xPosition" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testRequestSignatureYPositionWrongType(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 3,'yPosition' => 'wrong', 'xPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "yPosition" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testRequestSignaturePageWrongType(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['page' => 'wrong' ,'yPosition' => 400, 'xPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "page" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testSignaturesRequestOverDefault(): void
    {
        $requestProphecy = $this->prophesize(Request::class);
        $requestProphecy->get('signatures')->willReturn([
            ['yPosition' => 400, 'xPosition' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($requestProphecy->reveal());
        $signatureExtractor->setSignaturesOverridable(true);
        $signatureExtractor->setDefaultSignatures([
            'Quote' => [
                'signatures' => [
                    ['page' => 3, 'xPosition' => 350, 'yPosition' => 500],
                    ['page' => 2, 'xPosition' => 600, 'yPosition' => 100],
                ]
            ]
        ]);

        $this->assertEquals([
            ['page' => 1, 'yPosition' => 400, 'xPosition' => 500],
        ], $signatureExtractor->getSignatures());
    }
}
