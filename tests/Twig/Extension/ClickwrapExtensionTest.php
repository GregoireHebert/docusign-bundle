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

namespace DocusignBundle\tests\Twig\Extension;

use DocusignBundle\Twig\Extension\ClickwrapExtension;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Twig\Environment;

/**
 * @author Vincent Chalamon <vincent@les-tilleuls.coop>
 */
final class ClickwrapExtensionTest extends TestCase
{
    private $extension;
    private $twigMock;

    protected function setUp(): void
    {
        $this->twigMock = $this->prophesize(Environment::class);

        $this->extension = new ClickwrapExtension();
        $this->extension->addConfig('default', true, [
            'environment' => 'https://www.docusign.net',
            'accountId' => 'accountId-default',
            'clientUserId' => 'clientUserId-default',
            'clickwrapId' => 'clickwrapId-default',
        ]);
        $this->extension->addConfig('terms', true, [
            'environment' => 'https://www.docusign.net',
            'accountId' => 'accountId-terms',
            'clientUserId' => 'clientUserId-terms',
            'clickwrapId' => 'clickwrapId-terms',
        ]);
    }

    public function testItCannotLoadInvalidConfig(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->twigMock->render('@Docusign/clickwrap.html.twig', Argument::type('array'))->shouldNotBeCalled();

        $this->extension->renderClickwrap($this->twigMock->reveal(), 'invalid');
    }

    public function testItRendersClickwrap(): void
    {
        $this->twigMock->render('@Docusign/clickwrap.html.twig', [
            'environment' => 'https://demo.docusign.net',
            'accountId' => 'accountId-terms',
            'clientUserId' => 'clientUserId-terms',
            'clickwrapId' => 'clickwrapId-terms',
        ])->willReturn('foo')->shouldBeCalledOnce();

        $this->assertEquals('foo', $this->extension->renderClickwrap($this->twigMock->reveal(), 'terms'));
    }
}
