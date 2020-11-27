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

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\Exception\AmbiguousDocumentSelectionException;
use DocusignBundle\Tests\ProphecyTrait;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class SignatureExtractorTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ObjectProphecy|RequestStack
     */
    private $requestStackMock;

    /**
     * @var ObjectProphecy|ParameterBag
     */
    private $queryMock;

    protected function setUp(): void
    {
        $this->requestStackMock = $this->prophesize(RequestStack::class);
        $requestMock = $this->prophesize(Request::class);
        $requestMock->query = $this->queryMock = $this->prophesize(ParameterBag::class);

        $this->requestStackMock->getCurrentRequest()->willReturn($requestMock);
    }

    public function testItExtractsDefaultSignaturesWithType(): void
    {
        $this->queryMock->get('signatures')->willReturn(null);
        $this->queryMock->get('document_type')->willReturn('Quote');

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), false, [
            'Quote' => [
                ['page' => 3, 'x_position' => 350, 'y_position' => 500],
                ['page' => 2, 'x_position' => 600, 'y_position' => 100],
            ],
            'Receipt' => [
                ['page' => 1, 'x_position' => 250, 'y_position' => 50],
            ],
        ]);

        $this->assertEquals([
            ['page' => 3, 'x_position' => 350, 'y_position' => 500],
            ['page' => 2, 'x_position' => 600, 'y_position' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testItCannotExtractNoSignatures(): void
    {
        $this->queryMock->get('signatures')->willReturn(null);
        $this->queryMock->get('document_type')->willReturn(null);

        $this->assertNull((new SignatureExtractor($this->requestStackMock->reveal(), false, []))->getSignatures());
    }

    public function testItThrowsAnErrorOnExtractDefaultSignaturesWithAmbiguity(): void
    {
        $this->queryMock->get('signatures')->willReturn(null);
        $this->queryMock->get('document_type')->willReturn(null);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), false, [
            'Quote' => [
                ['page' => 3, 'x_position' => 350, 'y_position' => 500],
                ['page' => 2, 'x_position' => 600, 'y_position' => 100],
            ],
            'Receipt' => [
                ['page' => 1, 'x_position' => 250, 'y_position' => 50],
            ],
        ]);

        $this->expectException(AmbiguousDocumentSelectionException::class);
        $signatureExtractor->getSignatures();
    }

    public function testItExtractsDefaultSignaturesWithNoAmbiguity(): void
    {
        $this->queryMock->get('signatures')->willReturn(null);
        $this->queryMock->get('document_type')->willReturn(null);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), false, [
            'Quote' => [
                ['page' => 3, 'x_position' => 350, 'y_position' => 500],
                ['page' => 2, 'x_position' => 600, 'y_position' => 100],
            ],
        ]);

        $this->assertEquals([
            ['page' => 3, 'x_position' => 350, 'y_position' => 500],
            ['page' => 2, 'x_position' => 600, 'y_position' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testItThrowsAnErrorOnInvalidRequestSignature(): void
    {
        $this->queryMock->get('signatures')->willReturn('invalid');

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The parameter `signatures` must be an array of signatures, with the `page` (optional, default is 1), the `x_position` and the `y_position` values.');
        $signatureExtractor->getSignatures();
    }

    public function testTheDefaultSignatureCannotBeOverridden(): void
    {
        $this->queryMock->get('document_type')->willReturn(null);
        $this->queryMock->get('signatures')->shouldNotBeCalled();

        $this->assertNull((new SignatureExtractor($this->requestStackMock->reveal(), false, []))->getSignatures());
    }

    public function testItExtractsSignatureFromRequest(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 3, 'x_position' => 350, 'y_position' => 500],
            ['x_position' => 600, 'y_position' => 100],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->assertEquals([
            ['page' => 3, 'x_position' => 350, 'y_position' => 500],
            ['page' => 1, 'x_position' => 600, 'y_position' => 100],
        ], $signatureExtractor->getSignatures());
    }

    public function testItThrowsAnErrorOnMissingXPosition(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 3, 'y_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "x_position" is missing.');
        $signatureExtractor->getSignatures();
    }

    public function testItThrowsAnErrorOnMissingYPosition(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 3, 'x_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(MissingOptionsException::class);
        $this->expectExceptionMessage('The required option "y_position" is missing.');
        $signatureExtractor->getSignatures();
    }

    public function testItThrowsAnErrorOnXPositionWrongType(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 3, 'x_position' => 'wrong', 'y_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "x_position" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testItThrowsAnErrorOnYPositionWrongType(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 3, 'y_position' => 'wrong', 'x_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "y_position" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testItThrowsAnErrorOnPageWrongType(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['page' => 'wrong', 'y_position' => 400, 'x_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, []);

        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('The option "page" with value "wrong" is expected to be of type "int", but is of type "string".');
        $signatureExtractor->getSignatures();
    }

    public function testItExtractsSignaturesRequestOverDefault(): void
    {
        $this->queryMock->get('signatures')->willReturn([
            ['y_position' => 400, 'x_position' => 500],
        ]);

        $signatureExtractor = new SignatureExtractor($this->requestStackMock->reveal(), true, [
            'Quote' => [
                ['page' => 3, 'x_position' => 350, 'y_position' => 500],
                ['page' => 2, 'x_position' => 600, 'y_position' => 100],
            ],
        ]);

        $this->assertEquals([
            ['page' => 1, 'y_position' => 400, 'x_position' => 500],
        ], $signatureExtractor->getSignatures());
    }
}
