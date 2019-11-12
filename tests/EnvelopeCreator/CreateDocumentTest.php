<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocuSign\eSign\Model\Document;
use DocusignBundle\EnvelopeBuilderInterface;
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
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->fileSystemProphecyMock = $this->prophesize(FilesystemInterface::class);
    }

    public function testMissingFile(): void
    {
        $createDocument = new CreateDocument();

        $this->fileSystemProphecyMock->read(Argument::any())->shouldBeCalled()->willReturn(false);
        $this->envelopeBuilderProphecyMock->getFileSystem()->willReturn($this->fileSystemProphecyMock->reveal());
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled();

        $this->expectException(FileNotFoundException::class);
        $createDocument($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testHandle(): void
    {
        $createDocument = new CreateDocument();

        $this->fileSystemProphecyMock->read(Argument::any())->shouldBeCalled()->willReturn('bytes');
        $this->envelopeBuilderProphecyMock->getFileSystem()->shouldBeCalled()->willReturn($this->fileSystemProphecyMock->reveal());
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled()->willReturn('julienclair.mp3');
        $this->envelopeBuilderProphecyMock->getDocReference()->shouldBeCalled()->willReturn(1);
        $this->envelopeBuilderProphecyMock->getSignerName()->shouldBeCalled()->willReturn('Julien');
        $this->envelopeBuilderProphecyMock->getSignerEmail()->shouldBeCalled()->willReturn('julien@clair.sing');
        $this->envelopeBuilderProphecyMock->addSigner('Julien', 'julien@clair.sing')->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setDocument(Argument::type(Document::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->getDocument()->shouldBeCalled();

        $createDocument($envelopeBuilder = $this->envelopeBuilderProphecyMock->reveal());
        $this->assertInstanceOf(EnvelopeBuilderInterface::class, $envelopeBuilder);
        $this->assertInstanceOf(Document::class, $document = $envelopeBuilder->getDocument());
    }
}
