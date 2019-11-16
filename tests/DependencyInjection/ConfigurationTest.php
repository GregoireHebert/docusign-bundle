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
                    'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
                    'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
                    'ttl' => 1600,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'demo' => false,
            'auth_jwt' => [
                'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
                'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
                'ttl' => 1600,
            ],
            'account_id' => 1234567,
            'default_signer_name' => 'Grégoire Hébert',
            'default_signer_email' => 'gregoire@les-tilleuls.coop',
            'api_uri' => 'https://www.docusign.net/restapi',
            'callback_route_name' => 'docusign_callback',
            'webhook_route_name' => 'docusign_webhook',
            'signatures_overridable' => false,
            'signatures' => [],
            'storages' => [],
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
                    'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
                    'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
                    'ttl' => 2400,
                ],
                'account_id' => 1234567,
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
                'signatures_overridable' => true,
                'signatures' => [
                    'my_document' => [
                        'signatures' => [
                            [
                                'page' => 1,
                                'x_position' => 200,
                                'y_position' => 300,
                            ],
                        ],
                    ],
                ],
                'storages' => [
                    'MyStorage' => [
                        'adapter' => 'MyAdapter',
                        'options' => ['options' => 'MyOption'],
                        'visibility' => 'MyVisibility',
                        'case_sensitive' => false,
                        'disable_asserts' => false,
                    ],
                ],
            ],
            'storages' => [],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'demo' => false,
            'auth_jwt' => [
                'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                'integration_key' => 'c9763e9d-74e1-4370-9889-d749efd2b2ac',
                'user_guid' => '1d57a6cb-4fb0-4fb2-9fd9-09051f5b07ba',
                'ttl' => 2400,
            ],
            'account_id' => 1234567,
            'default_signer_name' => 'Grégoire Hébert',
            'default_signer_email' => 'gregoire@les-tilleuls.coop',
            'api_uri' => 'https://www.docusign.net/restapi',
            'callback_route_name' => 'docusign_callback',
            'webhook_route_name' => 'docusign_webhook',
            'signatures_overridable' => true,
            'signatures' => [
                'my_document' => [
                    'signatures' => [
                        [
                            'page' => 1,
                            'x_position' => 200,
                            'y_position' => 300,
                        ],
                    ],
                ],
            ],
            'storages' => [
                'MyStorage' => [
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
