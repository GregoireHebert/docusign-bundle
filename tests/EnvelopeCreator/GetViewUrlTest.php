<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace DocusignBundle\Tests\EnvelopeCreator;

use DocuSign\eSign\Model\RecipientViewRequest;
use DocuSign\eSign\Model\ViewUrl;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\GetViewUrl;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

class GetViewUrlTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
    }

    public function testItCreatesARecipient(): void
    {
        $viewUrlProphecyMock = $this->prophesize(ViewUrl::class);
        $viewUrlProphecyMock->getUrl()->willReturn('https://website.tld/route');

        $this->envelopeBuilderProphecyMock->getAccountId()->willReturn(1234567);
        $this->envelopeBuilderProphecyMock->getEnvelopeId()->willReturn('envelopeId');
        $this->envelopeBuilderProphecyMock->getSignerName()->willReturn('Grégoire Hébert');
        $this->envelopeBuilderProphecyMock->getSignerEmail()->willReturn('gregoire@les-tilleuls.coop');
        $this->envelopeBuilderProphecyMock->getViewUrl(Argument::type(RecipientViewRequest::class))->willReturn('http://docusign.com/url');
        $this->envelopeBuilderProphecyMock->getCallback()->willReturn('route_name');
        $this->envelopeBuilderProphecyMock->getCallbackParameters()->willReturn(['callbackParameter' => 'parameterValue']);
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');
        $this->envelopeBuilderProphecyMock->getMode()->willReturn(EnvelopeBuilder::MODE_EMBEDDED);

        $this->routerProphecyMock->generate('route_name', ['envelopeId' => 'envelopeId', 'callbackParameter' => 'parameterValue'], Router::ABSOLUTE_URL)->shouldBeCalled()->willReturn('https://website.tld/route?envelopeId=envelopeId&callbackParameter=parameterValue');

        $createRecipient = new GetViewUrl($this->envelopeBuilderProphecyMock->reveal(), $this->routerProphecyMock->reveal());
        $createRecipient(['signature_name' => 'default']);
    }
}
