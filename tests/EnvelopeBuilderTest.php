<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Exception\UnableToSignException;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

class EnvelopeBuilderTest extends TestCase
{
    public function testForgottenSetFile(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $routerProphecy = $this->prophesize(RouterInterface::class);
        $fileSystemProphecy = $this->prophesize(FilesystemInterface::class);
        $envelopeBuilder = new EnvelopeBuilder($loggerProphecy->reveal(), $routerProphecy->reveal(), $fileSystemProphecy->reveal(), 'dummyToken', 'dummyId', 'dummyName', 'dummyemail@domain.tld', 'dummyURI', 'dummyCallbackRoute', 'dummyWebhookRoute');
        $this->expectException(InvalidArgumentException::class);
        $envelopeBuilder->createEnvelope();
    }

    public function testUnableToCreateEnvelope(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $routerProphecy = $this->prophesize(RouterInterface::class);
        $routerProphecy->generate('dummyCallbackRoute');
        $routerProphecy->generate('dummyWebHoookRoute');

        $fileSystemProphecy = $this->prophesize(FilesystemInterface::class);
        $fileSystemProphecy->read('dummyFilePath.pdf')->willReturn('dummyFileContent');

        $envelopeBuilder = new EnvelopeBuilder($loggerProphecy->reveal(), $routerProphecy->reveal(), $fileSystemProphecy->reveal(), 'dummyToken', 'dummyId', 'dummyName', 'dummyemail@domain.tld', 'dummyURI', 'dummyCallbackRoute', 'dummyWebhookRoute');

        $this->expectException(UnableToSignException::class);
        $envelopeBuilder
            ->setFile('dummyFilePath.pdf')
            ->addSignatureZone(1, 2, 3)
            ->createEnvelope();
    }
}
