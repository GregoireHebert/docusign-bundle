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

use DocuSign\eSign\Model\Document;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\CreateDocument;
use DocusignBundle\Exception\FileNotFoundException;
use DocusignBundle\Filesystem\FilesystemInterface;
use DocusignBundle\Tests\ProphecyTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CreateDocumentTest extends TestCase
{
    use ProphecyTrait;

    private $envelopeBuilderProphecyMock;
    private $fileSystemProphecyMock;

    protected function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->fileSystemProphecyMock = $this->prophesize(FilesystemInterface::class);
    }

    public function testItThrowsAnErrorOnMissingFile(): void
    {
        $this->envelopeBuilderProphecyMock->getFileContent()->shouldBeCalled()->willReturn(false);
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled()->willReturn(null);
        $this->envelopeBuilderProphecyMock->getName()->shouldBeCalled()->willReturn('default');

        $this->expectException(FileNotFoundException::class);
        $createDocument = new CreateDocument($this->envelopeBuilderProphecyMock->reveal());
        $createDocument(['signature_name' => 'default']);
    }

    public function testItCreatesADocument(): void
    {
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled()->willReturn('julienclair.mp3');
        $this->envelopeBuilderProphecyMock->getFileContent()->shouldBeCalled()->willReturn('bytes');
        $this->envelopeBuilderProphecyMock->getDocReference()->shouldBeCalled()->willReturn(1);
        $this->envelopeBuilderProphecyMock->setDefaultSigner()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setDocument(Argument::type(Document::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->getDocument()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');

        $createDocument = new CreateDocument($envelopeBuilder = $this->envelopeBuilderProphecyMock->reveal());
        $createDocument(['signature_name' => 'default']);
        $this->assertInstanceOf(EnvelopeBuilderInterface::class, $envelopeBuilder);
        $this->assertInstanceOf(Document::class, $document = $envelopeBuilder->getDocument());
    }
}
