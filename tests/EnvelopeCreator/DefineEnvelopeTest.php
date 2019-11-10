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

use DocuSign\eSign\Model\EnvelopeDefinition;
use DocusignBundle\DocusignBundle;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\DefineEnvelope;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DefineEnvelopeTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;
    private $translatorProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->translatorProphecyMock = $this->prophesize(TranslatorInterface::class);
    }

    public function testItCreatesTheEnvelopeDefinition(): void
    {
        $this->envelopeBuilderProphecyMock->getDocument()->willReturn(null);
        $this->envelopeBuilderProphecyMock->getName()->willReturn('default');
        $this->envelopeBuilderProphecyMock->getSigners()->willReturn([]);
        $this->envelopeBuilderProphecyMock->getCarbonCopies()->willReturn([]);
        $this->envelopeBuilderProphecyMock->getWebhookParameters()->willReturn(['parameter' => 'value']);
        $this->envelopeBuilderProphecyMock->setEnvelopeDefinition(Argument::allOf(
            Argument::type(EnvelopeDefinition::class),
            Argument::which('getEmailSubject', DefineEnvelope::EMAIL_SUBJECT),
            Argument::which('getStatus', 'sent')
        ))->shouldBeCalled();

        $this->routerProphecyMock->generate('docusign_webhook', ['parameter' => 'value'], Router::ABSOLUTE_URL)->shouldBeCalled();

        $this->translatorProphecyMock->trans(Argument::type('string'), [], DocusignBundle::TRANSLATION_DOMAIN)->shouldBeCalled()->willReturn(DefineEnvelope::EMAIL_SUBJECT);

        $defineEnvelope = new DefineEnvelope($this->envelopeBuilderProphecyMock->reveal(), $this->routerProphecyMock->reveal());
        $defineEnvelope->setTranslator($this->translatorProphecyMock->reveal());
        $defineEnvelope(['signature_name' => 'default']);
    }
}
