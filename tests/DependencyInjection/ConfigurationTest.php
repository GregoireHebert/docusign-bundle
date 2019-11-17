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

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Processor
     */
    private $processor;

    protected function setUp(): void
    {
        $this->configuration = new Configuration();
        $this->processor = new Processor();
    }

    public function testItLoadsDefaultConfig(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'mode' => 'embedded',
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'e6a5e84b-8f67-4f18-ad35-2cce6a5814c6',
                    'user_guid' => 'be385011-09a7-4bbf-bfb4-9f9f06f9c8d9',
                    'ttl' => 1600,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'storage' => 'flysystem.adapter.name',
                'sign_path' => '/foo/sign',
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);

        $this->assertEquals([
            'default' => [
                'mode' => 'embedded',
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'e6a5e84b-8f67-4f18-ad35-2cce6a5814c6',
                    'user_guid' => 'be385011-09a7-4bbf-bfb4-9f9f06f9c8d9',
                    'ttl' => 1600,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'api_uri' => 'https://www.docusign.net/restapi',
                'callback' => 'docusign_callback',
                'sign_path' => '/foo/sign',
                'signatures_overridable' => false,
                'signatures' => [],
                'storage' => [
                    'storage' => 'flysystem.adapter.name',
                    'options' => [],
                    'visibility' => null,
                    'case_sensitive' => true,
                    'disable_asserts' => false,
                ],
            ],
        ], $config);
    }

    public function testItThrowsAnErrorOnInvalidModeConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "invalid" is not allowed for path "docusign.default.mode". Permissible values: "remote", "embedded"');
        $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'mode' => 'invalid',
            ],
        ]);
    }

    public function testItThrowsAnErrorOnMissingModeConfig(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "mode" at path "docusign.demo" must be configured.');
        $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'demo' => true,
            ],
        ]);
    }

    public function testItNormalizesConfig(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'mode' => 'embedded',
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'e6a5e84b-8f67-4f18-ad35-2cce6a5814c6',
                    'user_guid' => 'be385011-09a7-4bbf-bfb4-9f9f06f9c8d9',
                    'ttl' => 2400,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'signatures_overridable' => true,
                'sign_path' => '/foo/sign',
                'signatures' => [
                    'my_document' => [
                        [
                            'page' => 1,
                            'x_position' => 200,
                            'y_position' => 300,
                        ],
                    ],
                ],
                'storage' => [
                    'adapter' => 'MyAdapter',
                    'options' => ['options' => 'MyOption'],
                    'visibility' => 'MyVisibility',
                    'case_sensitive' => false,
                    'disable_asserts' => false,
                ],
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'default' => [
                'mode' => 'embedded',
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'e6a5e84b-8f67-4f18-ad35-2cce6a5814c6',
                    'user_guid' => 'be385011-09a7-4bbf-bfb4-9f9f06f9c8d9',
                    'ttl' => 2400,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'api_uri' => 'https://www.docusign.net/restapi',
                'callback' => 'docusign_callback',
                'sign_path' => '/foo/sign',
                'signatures_overridable' => true,
                'signatures' => [
                    'my_document' => [
                        [
                            'page' => 1,
                            'x_position' => 200,
                            'y_position' => 300,
                        ],
                    ],
                ],
                'storage' => [
                    'adapter' => 'MyAdapter',
                    'options' => ['options' => 'MyOption'],
                    'visibility' => 'MyVisibility',
                    'case_sensitive' => false,
                    'disable_asserts' => false,
                ],
            ],
        ], $config);
    }
}
