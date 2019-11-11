<?php

declare(strict_types=1);

namespace DocusignBundle\Tests;

use DocuSign\eSign\ApiException;
use DocuSign\eSign\Model\ViewUrl;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeCreator\CreateDocument;
use DocusignBundle\EnvelopeCreator\CreateRecipient;
use DocusignBundle\EnvelopeCreator\DefineEnvelope;
use DocusignBundle\EnvelopeCreator\EnvelopeCreator;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\Exception\UnableToSignException;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class EnvelopeCreatorTest extends TestCase
{

    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;
    private $loggerProphecyMock;
    private $stopwatchProphecyMock;
    private $createDocumentProphecyMock;
    private $createRecipientProphecyMock;
    private $defineEnvelopeProphecyMock;
    private $sendEnvelopeProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilder::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->loggerProphecyMock = $this->prophesize(LoggerInterface::class);
        $this->stopwatchProphecyMock = $this->prophesize(Stopwatch::class);
        $this->createDocumentProphecyMock = $this->prophesize(CreateDocument::class);
        $this->createRecipientProphecyMock = $this->prophesize(CreateRecipient::class);
        $this->defineEnvelopeProphecyMock = $this->prophesize(DefineEnvelope::class);
        $this->sendEnvelopeProphecyMock = $this->prophesize(SendEnvelope::class);
    }

    public function testMissingFilePath()
    {
        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value other than null');
        $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testMissingDocReference()
    {
        $this->envelopeBuilderProphecyMock->filePath = 'file/path';

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: null');
        $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testRemoteSignatureWithRouteName()
    {
        $this->envelopeBuilderProphecyMock->filePath = 'file/path';
        $this->envelopeBuilderProphecyMock->docReference = 'file/path';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'callback/routename';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_REMOTE;

        $this->stopwatchProphecyMock->start(Argument::type('string'))->shouldBeCalled();
        $this->stopwatchProphecyMock->stop(Argument::type('string'))->shouldBeCalled();

        $this->createDocumentProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->createRecipientProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldNotBeCalled();

        $this->routerProphecyMock->generate('callback/routename', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldBeCalled()->willReturn('route/to/redirect');

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->assertEquals('route/to/redirect', $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal()));
    }

    public function testRemoteSignatureWithURL()
    {
        $this->envelopeBuilderProphecyMock->filePath = 'file/path';
        $this->envelopeBuilderProphecyMock->docReference = 'file/path';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'http://website.tld/callback/routename';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_REMOTE;

        $this->stopwatchProphecyMock->start(Argument::type('string'))->shouldBeCalled();
        $this->stopwatchProphecyMock->stop(Argument::type('string'))->shouldBeCalled();

        $this->createDocumentProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->createRecipientProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldNotBeCalled();

        $this->routerProphecyMock->generate('callback/routename', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldNotBeCalled();

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->assertEquals('http://website.tld/callback/routename', $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal()));
    }

    public function testEmbeddedSignature()
    {
        $this->envelopeBuilderProphecyMock->filePath = 'file/path';
        $this->envelopeBuilderProphecyMock->docReference = 'file/path';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'http://website.tld/callback/routename';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_EMBEDDED;

        $this->stopwatchProphecyMock->start(Argument::type('string'))->shouldBeCalled();
        $this->stopwatchProphecyMock->stop(Argument::type('string'))->shouldBeCalled();

        $viewUrlProphecyMock = $this->prophesize(ViewUrl::class);
        $viewUrlProphecyMock->getUrl()->shouldBeCalled()->willReturn('http://docusign.com/url/to/redirect');

        $this->createDocumentProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->createRecipientProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled()->willReturn($viewUrlProphecyMock->reveal());

        $this->routerProphecyMock->generate('callback/routename', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldNotBeCalled();

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->assertEquals('http://docusign.com/url/to/redirect', $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal()));
    }

    public function testUnReachableDocusign()
    {
        $this->envelopeBuilderProphecyMock->filePath = 'file/path';
        $this->envelopeBuilderProphecyMock->docReference = 'file/path';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'http://website.tld/callback/routename';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_EMBEDDED;

        $this->stopwatchProphecyMock->start(Argument::type('string'))->shouldBeCalled();
        $this->stopwatchProphecyMock->stop(Argument::type('string'))->shouldBeCalled();

        $this->createDocumentProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldBeCalled()->will(function () { throw new ApiException(); });
        $this->createRecipientProphecyMock->handle(Argument::type(EnvelopeBuilder::class))->shouldNotBeCalled();

        $this->loggerProphecyMock->critical(Argument::type('string'), Argument::type('array'))->shouldBeCalled();

        $this->routerProphecyMock->generate('callback/routename', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldNotBeCalled();

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchProphecyMock->reveal(),
            $this->createDocumentProphecyMock->reveal(),
            $this->createRecipientProphecyMock->reveal(),
            $this->defineEnvelopeProphecyMock->reveal(),
            $this->sendEnvelopeProphecyMock->reveal()
        );

        $this->expectException(UnableToSignException::class);
        $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal());
    }
}
