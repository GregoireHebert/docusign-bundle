<?php

declare(strict_types=1);

namespace DocusignBundle\Tests\DependencyInjection;

use DocusignBundle\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
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

    public function testDefaultConfig(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'yourIntegrationKey',
                    'user_guid' => 'yourUserGuid',
                    'ttl' => 1600,
                ],
                'account_id' => 'ID',
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'storage' => 'flysystem.adapter.name'
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);

        $this->assertEquals([
            'default' => [
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'yourIntegrationKey',
                    'user_guid' => 'yourUserGuid',
                    'ttl' => 1600,
                ],
                'account_id' => 'ID',
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'api_uri' => 'https://www.docusign.net/restapi',
                'callback_route_name' => 'docusign_callback',
                'signatures_overridable' => false,
                'signatures' => [],
                'storage' => [
                    'storage' => 'flysystem.adapter.name',
                    'options' => [],
                    'visibility' => null,
                    'case_sensitive' => true,
                    'disable_asserts' => false,
                ]
            ],
        ], $config);
    }

    public function testConfig(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'yourIntegrationKey',
                    'user_guid' => 'yourUserGuid',
                    'ttl' => 2400,
                ],
                'account_id' => 'ID',
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
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
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'default' => [
                'demo' => false,
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => 'yourIntegrationKey',
                    'user_guid' => 'yourUserGuid',
                    'ttl' => 2400,
                ],
                'account_id' => 'ID',
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'api_uri' => 'https://www.docusign.net/restapi',
                'callback_route_name' => 'docusign_callback',
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
