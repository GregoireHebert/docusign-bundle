<?php

declare(strict_types=1);

namespace DocusignBundle\Tests;

use DocuSign\eSign\Api\EnvelopesApi;
use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\ViewUrl;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeCreator\CreateRecipient;
use League\Flysystem\FileNotFoundException;
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
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilder::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
    }

    public function testRouteNameRecipient()
    {
        $envelopeApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $envelopeApiProphecyMock->createRecipientView('accountId', 'envelopeId', Argument::allOf(
            Argument::type(RecipientViewRequest::class),
            Argument::which('getAuthenticationMethod', 'NONE'),
            Argument::which('getClientUserId', 'accountId'),
            Argument::which('getRecipientId', '1'),
            Argument::which('getReturnUrl', 'callBackUrl'),
            Argument::which('getUserName', 'Grégoire Hébert'),
            Argument::which('getEmail', 'gregoire@les-tilleuls.coop')
        ))->shouldBeCalled()->willReturn(new ViewUrl());

        $this->envelopeBuilderProphecyMock->accountId = 'accountId';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->signerName = 'Grégoire Hébert';
        $this->envelopeBuilderProphecyMock->signerEmail = 'gregoire@les-tilleuls.coop';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'callback/routename';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->envelopesApi = $envelopeApiProphecyMock->reveal();

        $this->routerProphecyMock->generate('callback/routename', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldBeCalled()->willReturn('callBackUrl');

        $createDocument = new CreateRecipient($this->routerProphecyMock->reveal());
        $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testRoutePathRecipient()
    {
        $envelopeApiProphecyMock = $this->prophesize(EnvelopesApi::class);
        $envelopeApiProphecyMock->createRecipientView('accountId', 'envelopeId', Argument::allOf(
            Argument::type(RecipientViewRequest::class),
            Argument::which('getAuthenticationMethod', 'NONE'),
            Argument::which('getClientUserId', 'accountId'),
            Argument::which('getRecipientId', '1'),
            Argument::which('getReturnUrl', 'https://website.tld/route'),
            Argument::which('getUserName', 'Grégoire Hébert'),
            Argument::which('getEmail', 'gregoire@les-tilleuls.coop')
        ))->shouldBeCalled()->willReturn(new ViewUrl());

        $this->envelopeBuilderProphecyMock->accountId = 'accountId';
        $this->envelopeBuilderProphecyMock->envelopeId = 'envelopeId';
        $this->envelopeBuilderProphecyMock->signerName = 'Grégoire Hébert';
        $this->envelopeBuilderProphecyMock->signerEmail = 'gregoire@les-tilleuls.coop';
        $this->envelopeBuilderProphecyMock->callbackRouteName = 'https://website.tld/route';
        $this->envelopeBuilderProphecyMock->callbackParameters = ['callbackParameter'=>'parameterValue'];
        $this->envelopeBuilderProphecyMock->envelopesApi = $envelopeApiProphecyMock->reveal();

        $this->routerProphecyMock->generate('https://website.tld/route', ['envelopeId'=>'envelopeId', 'callbackParameter'=>'parameterValue'], Router::ABSOLUTE_URL)->shouldNotBeCalled();

        $createDocument = new CreateRecipient($this->routerProphecyMock->reveal());
        $createDocument->handle($this->envelopeBuilderProphecyMock->reveal());
    }
}
