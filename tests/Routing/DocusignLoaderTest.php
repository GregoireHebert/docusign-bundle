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

namespace DocusignBundle\Tests\Routing;

use DocusignBundle\Routing\DocusignLoader;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DocusignLoaderTest extends TestCase
{
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new DocusignLoader([
            'foo' => [
                'mode' => 'embedded',
                'sign_path' => '/docusign/sign/foo',
                'callback' => 'docusign_callback',
            ],
            'bar' => [
                'mode' => 'embedded',
                'sign_path' => '/docusign/sign/bar/{key}',
                'callback' => 'http://www.example.com',
            ],
            'terms' => [
                'mode' => 'clickwrap',
            ],
        ]);
    }

    public function testItSupportsValidType(): void
    {
        $this->assertTrue($this->loader->supports(null, 'docusign'));
    }

    public function testItDoesNotSupportInvalidType(): void
    {
        $this->assertFalse($this->loader->supports(null, 'invalid'));
    }

    public function testItLoadsRoutes(): void
    {
        $routeCollection = $this->loader->load(null, 'invalid');

        $this->assertNotNull($route = $routeCollection->get('docusign_callback'));
        $this->assertEquals('/docusign/callback', $route->getPath());
        $this->assertEquals('docusign.callback', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_webhook'));
        $this->assertEquals('/docusign/webhook', $route->getPath());
        $this->assertEquals('docusign.webhook', $route->getDefault('_controller'));
        $this->assertEquals(['POST'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_foo'));
        $this->assertEquals('/docusign/sign/foo', $route->getPath());
        $this->assertEquals('docusign.sign.foo', $route->getDefault('_controller'));
        $this->assertEquals('foo', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_bar'));
        $this->assertEquals('/docusign/sign/bar/{key}', $route->getPath());
        $this->assertEquals('docusign.sign.bar', $route->getDefault('_controller'));
        $this->assertEquals('bar', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_callback_bar'));
        $this->assertEquals('/docusign/callback/bar', $route->getPath());
        $this->assertEquals('FrameworkBundle:Redirect:urlRedirect', $route->getDefault('_controller'));
        $this->assertEquals('http://www.example.com', $route->getDefault('path'));
        $this->assertTrue($route->getDefault('permanent'));
        $this->assertEquals('bar', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());
    }
}
