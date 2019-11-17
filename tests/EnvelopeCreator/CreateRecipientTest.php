<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\ViewUrl;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\CreateRecipient;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class CreateRecipientTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
    }

    public function testRecipient()
    {
        $viewUrlProphecyMock = $this->prophesize(ViewUrl::class);
        $viewUrlProphecyMock->getUrl()->willReturn('https://website.tld/route');

        $this->envelopeBuilderProphecyMock->getAccountId()->willReturn('accountId');
        $this->envelopeBuilderProphecyMock->getEnvelopeId()->willReturn('envelopeId');
        $this->envelopeBuilderProphecyMock->getSignerName()->willReturn('Grégoire Hébert');
        $this->envelopeBuilderProphecyMock->getSignerEmail()->willReturn('gregoire@les-tilleuls.coop');
        $this->envelopeBuilderProphecyMock->getViewUrl(Argument::type(RecipientViewRequest::class))->willReturn('http://docusign.com/url');
        $this->envelopeBuilderProphecyMock->getCallback()->willReturn('route_name');
        $this->envelopeBuilderProphecyMock->getCallbackParameters()->willReturn(['callbackParameter'=>'parameterValue']);
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');

        $this->routerProphecyMock->generate('route_name', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldBeCalled()->willReturn('https://website.tld/route?envelopeId=envelopeId&callbackParameter=parameterValue');

        $createRecipient = new CreateRecipient($this->envelopeBuilderProphecyMock->reveal(), $this->routerProphecyMock->reveal());
        $createRecipient(['signature_name'=>'default']);
    }
}
