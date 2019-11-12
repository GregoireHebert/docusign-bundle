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

    public function testRouteNameRecipient()
    {
        $viewUrlProphecyMock = $this->prophesize(ViewUrl::class);
        $viewUrlProphecyMock->getUrl()->willReturn('https://website.tld/route');

        $envelopeApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $envelopeApiProphecyMock->createRecipientView('accountId', 'envelopeId', Argument::allOf(
            Argument::type(RecipientViewRequest::class),
            Argument::which('getAuthenticationMethod', 'NONE'),
            Argument::which('getClientUserId', 'accountId'),
            Argument::which('getReturnUrl', 'https://website.tld/route?envelopeId=envelopeId&callbackParameter=parameterValue'),
            Argument::which('getRecipientId', '1'),
            Argument::which('getUserName', 'Grégoire Hébert'),
            Argument::which('getEmail', 'gregoire@les-tilleuls.coop')
        ))->shouldBeCalled()->willReturn($viewUrlProphecyMock->reveal());

        $this->envelopeBuilderProphecyMock->getAccountId()->willReturn('accountId');
        $this->envelopeBuilderProphecyMock->getEnvelopeId()->willReturn('envelopeId');
        $this->envelopeBuilderProphecyMock->getSignerName()->willReturn('Grégoire Hébert');
        $this->envelopeBuilderProphecyMock->getSignerEmail()->willReturn('gregoire@les-tilleuls.coop');
        $this->envelopeBuilderProphecyMock->getCallback()->willReturn('route_name');
        $this->envelopeBuilderProphecyMock->getCallbackParameters()->willReturn(['callbackParameter'=>'parameterValue']);
        $this->envelopeBuilderProphecyMock->getEnvelopesApi()->willReturn($envelopeApiProphecyMock->reveal());

        $this->routerProphecyMock->generate('route_name', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldBeCalled()->willReturn('https://website.tld/route?envelopeId=envelopeId&callbackParameter=parameterValue');

        $createDocument = new CreateRecipient($this->routerProphecyMock->reveal());
        $createDocument($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testRoutePathRecipient()
    {
        $viewUrlProphecyMock = $this->prophesize(ViewUrl::class);
        $viewUrlProphecyMock->getUrl()->willReturn('https://website.tld/route');

        $envelopeApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $envelopeApiProphecyMock->createRecipientView('accountId', 'envelopeId', Argument::allOf(
            Argument::type(RecipientViewRequest::class),
            Argument::which('getAuthenticationMethod', 'NONE'),
            Argument::which('getClientUserId', 'accountId'),
            Argument::which('getRecipientId', '1'),
            Argument::which('getReturnUrl', 'https://website.tld/route?envelopeId=envelopeId&callbackParameter=parameterValue'),
            Argument::which('getUserName', 'Grégoire Hébert'),
            Argument::which('getEmail', 'gregoire@les-tilleuls.coop')
        ))->shouldBeCalled()->willReturn($viewUrlProphecyMock->reveal());

        $this->envelopeBuilderProphecyMock->getAccountId()->willReturn('accountId');
        $this->envelopeBuilderProphecyMock->getEnvelopeId()->willReturn('envelopeId');
        $this->envelopeBuilderProphecyMock->getSignerName()->willReturn('Grégoire Hébert');
        $this->envelopeBuilderProphecyMock->getSignerEmail()->willReturn('gregoire@les-tilleuls.coop');
        $this->envelopeBuilderProphecyMock->getCallback()->willReturn('https://website.tld/route');
        $this->envelopeBuilderProphecyMock->getCallbackParameters()->willReturn(['callbackParameter'=>'parameterValue']);
        $this->envelopeBuilderProphecyMock->getEnvelopesApi()->willReturn($envelopeApiProphecyMock->reveal());

        $this->routerProphecyMock->generate('https://website.tld/route', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldNotBeCalled();

        $createRecipient = new CreateRecipient($this->routerProphecyMock->reveal());
        $createRecipient($this->envelopeBuilderProphecyMock->reveal());
    }
}
