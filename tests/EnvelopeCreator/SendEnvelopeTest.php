<?php

declare(strict_types=1);

namespace DocusignBundle\Tests;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Configuration;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\EnvelopeSummary;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\Grant\GrantInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;

class SendEnvelopeTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $grantProphecyMock;
    private $envelopesApiProphecyMock;
    private $envelopeSummaryProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilder::class);
        $this->grantProphecyMock = $this->prophesize(GrantInterface::class);
        $this->envelopesApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $this->envelopeSummaryProphecyMock = $this->prophesize(EnvelopeSummary::class);
    }

    public function testSendEnvelope(): void
    {
        $this->envelopeSummaryProphecyMock->getEnvelopeId()->shouldBeCalled()->willReturn('envelopeId');
        $this->envelopesApiProphecyMock->createEnvelope('accountId', 'envelopeDefinition')->shouldBeCalled()->willReturn($this->envelopeSummaryProphecyMock->reveal());

        $this->envelopeBuilderProphecyMock->accountId = 'accountId';
        $this->envelopeBuilderProphecyMock->envelopeDefinition = 'envelopeDefinition';
        $this->envelopeBuilderProphecyMock->envelopesApi = $this->envelopesApiProphecyMock->reveal();

        $this->grantProphecyMock->__invoke()->shouldBeCalled()->willReturn('grant');
        $this->envelopeBuilderProphecyMock->setEnvelopesApi(Argument::type(EnvelopesApi::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setEnvelopeId('envelopeId')->shouldBeCalled();

        $createDocument = new SendEnvelope($this->grantProphecyMock->reveal());
        $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
    }
}
