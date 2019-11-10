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

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\EnvelopeBuilderCallableInterface;
use DocusignBundle\EnvelopeCreator\EnvelopeCreator;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class EnvelopeCreatorTest extends TestCase
{
    private $envelopeBuilderProphecyMock;
    private $routerProphecyMock;
    private $loggerProphecyMock;
    private $createDocumentProphecyMock;
    private $createRecipientProphecyMock;
    private $defineEnvelopeProphecyMock;
    private $sendEnvelopeProphecyMock;

    public function setUp(): void
    {
        $this->envelopeBuilderProphecyMock = $this->prophesize(EnvelopeBuilderInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->loggerProphecyMock = $this->prophesize(LoggerInterface::class);
        $this->createDocumentProphecyMock = $this->prophesize(EnvelopeBuilderCallableInterface::class);
        $this->createRecipientProphecyMock = $this->prophesize(EnvelopeBuilderCallableInterface::class);
        $this->defineEnvelopeProphecyMock = $this->prophesize(EnvelopeBuilderCallableInterface::class);
        $this->sendEnvelopeProphecyMock = $this->prophesize(EnvelopeBuilderCallableInterface::class);
    }

    public function testMissingFilePath(): void
    {
        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            'default',
            [
                $this->createDocumentProphecyMock->reveal(),
                $this->defineEnvelopeProphecyMock->reveal(),
                $this->sendEnvelopeProphecyMock->reveal(),
                $this->createRecipientProphecyMock->reveal(),
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a value other than null');
        $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testMissingDocReference(): void
    {
        $this->envelopeBuilderProphecyMock->getFilePath()->willReturn('file/path');
        $this->envelopeBuilderProphecyMock->getDocReference()->willReturn(0);
        $this->envelopeBuilderProphecyMock->reset()->shouldBeCalled();

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            'default',
            [
                $this->createDocumentProphecyMock->reveal(),
                $this->defineEnvelopeProphecyMock->reveal(),
                $this->sendEnvelopeProphecyMock->reveal(),
                $this->createRecipientProphecyMock->reveal(),
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected a non-empty value. Got: 0');
        $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal());
    }

    public function testRemoteSignatureWithRouteName(): void
    {
        $this->envelopeBuilderProphecyMock->getFilePath()->willReturn('file/path');
        $this->envelopeBuilderProphecyMock->getDocReference()->willReturn(1);
        $this->envelopeBuilderProphecyMock->reset()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_REMOTE;

        $this->createDocumentProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled()->willReturn('route/to/redirect');
        $this->createRecipientProphecyMock->__invoke(Argument::type('array'))->shouldNotBeCalled();

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            'default',
            [
                $this->createDocumentProphecyMock->reveal(),
                $this->defineEnvelopeProphecyMock->reveal(),
                $this->sendEnvelopeProphecyMock->reveal(),
                $this->createRecipientProphecyMock->reveal(),
            ]
        );

        $this->assertEquals('route/to/redirect', $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal()));
    }

    public function testEmbeddedSignature(): void
    {
        $this->envelopeBuilderProphecyMock->getFilePath()->willReturn('file/path');
        $this->envelopeBuilderProphecyMock->getDocReference()->willReturn(1);
        $this->envelopeBuilderProphecyMock->getCallback()->willReturn('http://website.tld/callback/routename');
        $this->envelopeBuilderProphecyMock->getEnvelopeId()->willReturn('envelopeId');
        $this->envelopeBuilderProphecyMock->getCallbackParameters()->willReturn(['callbackParameter' => 'parameterValue']);
        $this->envelopeBuilderProphecyMock->reset()->shouldBeCalled();
        $this->envelopeBuilderProphecyMock->mode = EnvelopeBuilder::MODE_EMBEDDED;

        $this->createDocumentProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled();
        $this->defineEnvelopeProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled();
        $this->sendEnvelopeProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled();
        $this->createRecipientProphecyMock->__invoke(Argument::type('array'))->shouldBeCalled()->willReturn('http://docusign.com/url/to/redirect');

        $envelopeCreator = new EnvelopeCreator(
            $this->routerProphecyMock->reveal(),
            $this->loggerProphecyMock->reveal(),
            'default',
            [
                $this->createDocumentProphecyMock->reveal(),
                $this->defineEnvelopeProphecyMock->reveal(),
                $this->sendEnvelopeProphecyMock->reveal(),
                $this->createRecipientProphecyMock->reveal(),
            ]
        );

        $this->assertEquals('http://docusign.com/url/to/redirect', $envelopeCreator->createEnvelope($this->envelopeBuilderProphecyMock->reveal()));
    }
}
