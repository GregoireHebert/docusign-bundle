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

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\CreateSignature;
use DocusignBundle\Tests\ProphecyTrait;
use DocusignBundle\Utils\SignatureExtractor;
use PHPUnit\Framework\TestCase;

class CreateSignatureTest extends TestCase
{
    use ProphecyTrait;

    private $envelopeBuilderProphecyMock;
    private $signatureExtractorProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->signatureExtractorProphecyMock = $this->prophesize(SignatureExtractor::class);
    }

    public function testItThrowsAnErrorOnMissingSignatures(): void
    {
        $this->expectException(\LogicException::class);
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default')->shouldBeCalled();
        $this->signatureExtractorProphecyMock->getSignatures()->willReturn(null)->shouldBeCalled();

        $createSignature = new CreateSignature($this->envelopeBuilderProphecyMock->reveal(), $this->signatureExtractorProphecyMock->reveal());
        $createSignature(['signature_name' => 'default']);
    }

    public function testItAddsSignatureZone(): void
    {
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default')->shouldBeCalled();
        $this->signatureExtractorProphecyMock->getSignatures()->willReturn([
            [
                'page' => 1,
                'x_position' => 200,
                'y_position' => 300,
            ],
        ]);
        $this->envelopeBuilderProphecyMock->addSignatureZone(1, 200, 300)->shouldBeCalled();

        $createSignature = new CreateSignature($this->envelopeBuilderProphecyMock->reveal(), $this->signatureExtractorProphecyMock->reveal());
        $createSignature(['signature_name' => 'default']);
    }
}
