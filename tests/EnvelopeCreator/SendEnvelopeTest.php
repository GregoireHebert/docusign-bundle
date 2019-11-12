<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\EnvelopeDefinition;
use DocuSign\eSign\Model\EnvelopeSummary;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\SendEnvelope;
use DocusignBundle\Grant\GrantInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\RouterInterface;

class SendEnvelopeTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $grantProphecyMock;
    private $routerProphecyMock;
    private $envelopesApiProphecyMock;
    private $envelopeSummaryProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->grantProphecyMock = $this->prophesize(GrantInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->envelopesApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $this->envelopeSummaryProphecyMock = $this->prophesize(EnvelopeSummary::class);
    }

    public function testSendEnvelope(): void
    {
        $envelopeDefinitionProphecyMock = $this->prophesize(EnvelopeDefinition::class);
        $envelopeDefinitionProphecyMock->__toString()->willReturn('definition');
        $envelopeDefinition = $envelopeDefinitionProphecyMock->reveal();

        $this->envelopeSummaryProphecyMock->getEnvelopeId()->shouldBeCalled()->willReturn('envelopeId');
        $this->envelopesApiProphecyMock->createEnvelope('accountId', $envelopeDefinition)->shouldBeCalled()->willReturn($this->envelopeSummaryProphecyMock->reveal());

        $this->envelopeBuilderProphecyMock->getEnvelopeDefinition()->shouldBeCalled()->willReturn($envelopeDefinition);
        $this->envelopeBuilderProphecyMock->getAccountId()->shouldBeCalled()->willReturn('accountId');
        $this->envelopeBuilderProphecyMock->getMode()->shouldBeCalled()->willReturn('embedded');
        $this->envelopeBuilderProphecyMock->getApiUri()->shouldBeCalled()->willReturn('uri');
        $this->envelopeBuilderProphecyMock->getEnvelopesApi()->shouldBeCalled()->willReturn($this->envelopesApiProphecyMock->reveal());

        $this->grantProphecyMock->__invoke()->shouldBeCalled()->willReturn('grant');
        $this->envelopeBuilderProphecyMock->setEnvelopesApi(Argument::type(EnvelopesApi::class))->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->setEnvelopeId('envelopeId')->shouldBeCalled();

        $createDocument = new SendEnvelope($this->grantProphecyMock->reveal(), $this->routerProphecyMock->reveal(), 'default');
        $createDocument($this->envelopeBuilderProphecyMock->reveal(), ['signature_name' => 'default']);
    }
}
