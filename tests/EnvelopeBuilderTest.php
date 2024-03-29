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

namespace DocusignBundle\Tests;

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\EnvelopeCreator\EnvelopeCreatorInterface;
use DocusignBundle\Filesystem\FilesystemInterface;
use DocusignBundle\Grant\GrantInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Stopwatch\Stopwatch;

class EnvelopeBuilderTest extends TestCase
{
    use ProphecyTrait;

    private $loggerProphecyMock;
    private $routerProphecyMock;
    private $fileSystemProphecyMock;
    private $stopwatchMock;
    private $grantProphecyMock;
    private $envelopeCreatorProphecyMock;
    /** @var EnvelopeBuilder */
    private $envelopeBuilder;

    protected function setUp(): void
    {
        $this->loggerProphecyMock = $this->prophesize(LoggerInterface::class);
        $this->routerProphecyMock = $this->prophesize(RouterInterface::class);
        $this->fileSystemProphecyMock = $this->prophesize(FilesystemInterface::class);
        $this->stopwatchMock = $this->prophesize(Stopwatch::class);
        $this->grantProphecyMock = $this->prophesize(GrantInterface::class);
        $this->envelopeCreatorProphecyMock = $this->prophesize(EnvelopeCreatorInterface::class);

        $this->envelopeCreatorProphecyMock->createEnvelope(Argument::type(EnvelopeBuilderInterface::class))->willReturn('/path/to/redirect');

        $this->envelopeBuilder = new EnvelopeBuilder(
            $this->fileSystemProphecyMock->reveal(),
            $this->envelopeCreatorProphecyMock->reveal(),
            1234567,
            'dummyName',
            'dummyemail@domain.tld',
            true,
            'http://dummy-uri.tld',
            'dummyCallbackRoute',
            'embedded',
            EnvelopeBuilder::AUTH_MODE_JWT,
            'default'
        );
    }

    public function testItCreatesARemoteSignatureEnvelope(): void
    {
        $this->fileSystemProphecyMock->read('dummyFilePath.pdf')->willReturn('dummyFileContent');
        $this->grantProphecyMock->__invoke()->willReturn('encoded_access_token');

        $redirectPath = $this->envelopeBuilder
            ->setFile('dummyFilePath.pdf')
            ->addSignatureZone(1, 2, 3)
            ->createEnvelope();

        $this->assertEquals('/path/to/redirect', $redirectPath);
    }

    public function testItChangesTheDefaultSigner(): void
    {
        $this->envelopeBuilder->setSignerName('John DOE');
        $this->envelopeBuilder->setSignerEmail('john.doe@example.com');

        $this->assertEquals('John DOE', $this->envelopeBuilder->getSignerName());
        $this->assertEquals('john.doe@example.com', $this->envelopeBuilder->getSignerEmail());
    }
}
