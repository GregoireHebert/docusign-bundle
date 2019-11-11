<?php

declare(strict_types=1);

namespace DocusignBundle\Tests;

use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\EventNotification;
use DocuSign\eSign\Model\Recipients;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeCreator\DefineEnvelope;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class DefineEnvelopeTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilder::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
    }

    public function testEnvelopeDefinition(): void
    {
        $this->envelopeBuilderProphecyMock->document = 'document';
        $this->envelopeBuilderProphecyMock->signers = 'signers';
        $this->envelopeBuilderProphecyMock->carbonCopies = 'carbonCopies';
        $this->envelopeBuilderProphecyMock->webhookParameters = ['parameter'=>'value'];
        $this->envelopeBuilderProphecyMock->webhookRouteName = 'docusign_webhook';
        $this->envelopeBuilderProphecyMock->setEnvelopeDefinition(Argument::allOf(
            Argument::type(EnvelopeDefinition::class),
            Argument::which('getEmailSubject', EnvelopeBuilder::EMAIL_SUBJECT),
            Argument::which('getStatus', 'sent')
        ))->shouldBeCalled();

        $this->routerProphecyMock->generate('docusign_webhook', ['parameter'=>'value'], Router::ABSOLUTE_URL)->shouldBeCalled();

        $createDocument = new DefineEnvelope($this->routerProphecyMock->reveal());
        $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
    }
}
