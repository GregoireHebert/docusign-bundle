<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\Routing;

use DocusignBundle\Routing\SignLoader;
use PHPUnit\Framework\TestCase;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class SignLoaderTest extends TestCase
{
    private $loader;

    protected function setUp(): void
    {
        $this->loader = new SignLoader([
            'foo' => [
                'sign_path' => '/docusign/sign/foo',
            ],
            'bar' => [
                'sign_path' => '/docusign/sign/bar/{key}',
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
        $this->assertEquals('docusign_callback', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_webhook'));
        $this->assertEquals('/docusign/webhook', $route->getPath());
        $this->assertEquals('docusign_webhook', $route->getDefault('_controller'));
        $this->assertEquals(['POST'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_foo'));
        $this->assertEquals('/docusign/sign/foo', $route->getPath());
        $this->assertEquals('docusign_sign', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());

        $this->assertNotNull($route = $routeCollection->get('docusign_sign_bar'));
        $this->assertEquals('/docusign/sign/bar/{key}', $route->getPath());
        $this->assertEquals('docusign_sign', $route->getDefault('_controller'));
        $this->assertEquals(['GET'], $route->getMethods());
    }
}
