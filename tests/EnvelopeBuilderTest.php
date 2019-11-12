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

namespace DocusignBundle\Tests\Bridge\FlySystem;

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Exception\UnableToSignException;
use DocusignBundle\Grant\GrantInterface;
use InvalidArgumentException;
use League\Flysystem\FilesystemInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class EnvelopeBuilderTest extends TestCase
{
    private $loggerProphecyMock;
    private $routerProphecyMock;
    private $fileSystemProphecyMock;
    private $stopwatchMock;
    private $grantProphecyMock;
    private $envelopeBuilder;

    protected function setUp(): void
    {
        $this->loggerProphecyMock = $this->prophesize(LoggerInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->fileSystemProphecyMock = $this->prophesize(FilesystemInterface::class);
        $this->stopwatchMock = $this->prophesize(Stopwatch::class);
        $this->grantProphecyMock = $this->prophesize(GrantInterface::class);

        $this->envelopeBuilder = new EnvelopeBuilder(
            $this->loggerProphecyMock->reveal(),
            $this->stopwatchMock->reveal(),
            $this->routerProphecyMock->reveal(),
            $this->fileSystemProphecyMock->reveal(),
            $this->grantProphecyMock->reveal(),
            'dummyId',
            'dummyName',
            'dummyemail@domain.tld',
            'dummyURI',
            'dummyCallbackRoute',
            'dummyWebhookRoute'
        );
    }

    public function testForgottenSetFile(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->envelopeBuilder->createEnvelope();
    }

    public function testUnableToCreateEnvelope(): void
    {
        $this->routerProphecyMock->generate('dummyCallbackRoute');
        $this->routerProphecyMock->generate('dummyWebHoookRoute');

        $this->fileSystemProphecyMock->read('dummyFilePath.pdf')->willReturn('dummyFileContent');
        $this->grantProphecyMock->__invoke()->willReturn('encoded_access_token');

        $this->expectException(UnableToSignException::class);
        $this->envelopeBuilder
            ->setFile('dummyFilePath.pdf')
            ->addSignatureZone(1, 2, 3)
            ->createEnvelope();
    }
}
