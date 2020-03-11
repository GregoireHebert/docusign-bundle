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
            'default' => [
                'mode' => 'embedded',
                'sign_path' => '/docusign/sign/embedded',
                'auth_jwt' => [],
            ],
            'remote' => [
                'mode' => 'remote',
                'sign_path' => '/docusign/sign/remote/{key}',
                'callback' => 'http://www.example.com',
                'auth_jwt' => [],
            ],
            'embedded_auth_code' => [
                'mode' => 'embedded',
                'sign_path' => '/docusign/sign/embedded_auth_code',
                'auth_code' => [],
            ],
            'remote_auth_code' => [
                'mode' => 'remote',
                'sign_path' => '/docusign/sign/remote_auth_code/{key}',
                'callback' => 'http://www.example.com',
                'auth_code' => [],
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

        $this->assertNotNull($route = $routeCollection->get('docusign_callback_default'));
        $this->assertEquals('/docusign/callback/default', $route->getPath());
        $this->assertEquals('docusign.callback.default', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_webhook_default'));
        $this->assertEquals('/docusign/webhook/default', $route->getPath());
        $this->assertEquals('docusign.webhook.default', $route->getDefault('_controller'));
        $this->assertEquals(['POST'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_default'));
        $this->assertEquals('/docusign/sign/embedded', $route->getPath());
        $this->assertEquals('docusign.sign.default', $route->getDefault('_controller'));
        $this->assertEquals('default', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_remote'));
        $this->assertEquals('/docusign/sign/remote/{key}', $route->getPath());
        $this->assertEquals('docusign.sign.remote', $route->getDefault('_controller'));
        $this->assertEquals('remote', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_callback_remote'));
        $this->assertEquals('/docusign/callback/remote', $route->getPath());
        $this->assertEquals('FrameworkBundle:Redirect:urlRedirect', $route->getDefault('_controller'));
        $this->assertEquals('http://www.example.com', $route->getDefault('path'));
        $this->assertTrue($route->getDefault('permanent'));
        $this->assertEquals('remote', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_callback_embedded_auth_code'));
        $this->assertEquals('/docusign/callback/embedded_auth_code', $route->getPath());
        $this->assertEquals('docusign.callback.embedded_auth_code', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_webhook_embedded_auth_code'));
        $this->assertEquals('/docusign/webhook/embedded_auth_code', $route->getPath());
        $this->assertEquals('docusign.webhook.embedded_auth_code', $route->getDefault('_controller'));
        $this->assertEquals(['POST'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_embedded_auth_code'));
        $this->assertEquals('/docusign/sign/embedded_auth_code', $route->getPath());
        $this->assertEquals('docusign.sign.embedded_auth_code', $route->getDefault('_controller'));
        $this->assertEquals('embedded_auth_code', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_remote_auth_code'));
        $this->assertEquals('/docusign/sign/remote_auth_code/{key}', $route->getPath());
        $this->assertEquals('docusign.sign.remote_auth_code', $route->getDefault('_controller'));
        $this->assertEquals('remote_auth_code', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_callback_remote_auth_code'));
        $this->assertEquals('/docusign/callback/remote_auth_code', $route->getPath());
        $this->assertEquals('FrameworkBundle:Redirect:urlRedirect', $route->getDefault('_controller'));
        $this->assertEquals('http://www.example.com', $route->getDefault('path'));
        $this->assertTrue($route->getDefault('permanent'));
        $this->assertEquals('remote_auth_code', $route->getDefault('_docusign_name'));
        $this->assertEquals(['GET'], $route->getMethods());
    }
}
