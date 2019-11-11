<?php

declare(strict_types=1);

namespace DocusignBundle\Tests;

use DocuSign\eSign\Model\Document;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeCreator\CreateDocument;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class CreateDocumentTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $fileSystemProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilder::class);
        $this->fileSystemProphecyMock = $this->prophesize(FilesystemInterface::class);
    }

    public function testMissingFile()
    {
        $createDocument = new CreateDocument();

        $this->fileSystemProphecyMock->read(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->envelopeBuilderProphecyMock->fileSystem = $this->fileSystemProphecyMock->reveal();

        $this->expectException(FileNotFoundException::class);
        $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testHandle()
    {
        $createDocument = new CreateDocument();

        $this->fileSystemProphecyMock->read(Argument::any())->shouldBeCalled()->willReturn('bytes');
        $this->envelopeBuilderProphecyMock->fileSystem = $this->fileSystemProphecyMock->reveal();
        $this->envelopeBuilderProphecyMock->filePath = 'julienclair.mp3';
        $this->envelopeBuilderProphecyMock->docReference = 'ma/p?reference/a/moi';
        $this->envelopeBuilderProphecyMock->signerName = 'Julien';
        $this->envelopeBuilderProphecyMock->signerEmail = 'julien@clair.sing';
        $this->envelopeBuilderProphecyMock->addSigner('Julien', 'julien@clair.sing')->shouldBeCalled();

        $envelopeBuilder = $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
        $this->assertInstanceOf(EnvelopeBuilder::class, $envelopeBuilder);
        $this->assertInstanceOf(Document::class, $envelopeBuilder->document);
        $this->assertEquals(base64_encode('bytes'), $envelopeBuilder->document->getDocumentBase64());
        $this->assertEquals('julienclair', $envelopeBuilder->document->getName());
        $this->assertEquals('mp3', $envelopeBuilder->document->getFileExtension());
        $this->assertEquals('ma/p?reference/a/moi', $envelopeBuilder->document->getDocumentId());
    }
}
