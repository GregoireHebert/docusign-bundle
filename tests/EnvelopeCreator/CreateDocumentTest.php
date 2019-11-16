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
        $this->envelopeBuilderProphecyMock->getFileContent()->shouldBeCalled()->willReturn(false);
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled()->willReturn(null);
        $this->envelopeBuilderProphecyMock->getName()->shouldBeCalled()->willReturn('default');

        $this->expectException(FileNotFoundException::class);
        $createDocument = new CreateDocument($this->envelopeBuilderProphecyMock->reveal());
        $createDocument(['signature_name'=> 'default']);
    }

    public function testHandle(): void
    {
        $this->envelopeBuilderProphecyMock->getFilePath()->shouldBeCalled()->willReturn('julienclair.mp3');
        $this->envelopeBuilderProphecyMock->getFileContent()->shouldBeCalled()->willReturn('bytes');
        $this->envelopeBuilderProphecyMock->getDocReference()->shouldBeCalled()->willReturn(1);
        $this->envelopeBuilderProphecyMock->setDefaultSigner()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setDocument(Argument::type(Document::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->getDocument()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');

        $createDocument = new CreateDocument($envelopeBuilder = $this->envelopeBuilderProphecyMock->reveal());
        $createDocument(['signature_name'=>'default']);

        $this->assertInstanceOf(EnvelopeBuilderInterface::class, $envelopeBuilder);
        $this->assertInstanceOf(Document::class, $document = $envelopeBuilder->getDocument());
    }
}
