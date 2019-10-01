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
                'access_token' => 'token',
                'account_id' => 'ID',
                'default_signer_name' => 'Grégoire Hébert',
                'default_signer_email' => 'gregoire@les-tilleuls.coop',
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'access_token' => 'token',
            'account_id' => 'ID',
            'default_signer_name' => 'Grégoire Hébert',
            'default_signer_email' => 'gregoire@les-tilleuls.coop',
            'api_uri' => 'https://demo.docusign.net/restapi',
            'callback_route_name' => 'docusign_callback',
            'webhook_route_name' => 'docusign_webhook',
            'signatures_overridable' => false,
            'signatures' => [],
        ], $config);
    }

    public function testConfig(): void
    {
        $treeBuilder = $this->configuration->getConfigTreeBuilder();
        $config = $this->processor->processConfiguration($this->configuration, [
            'docusign' => [
                'access_token' => 'token',
                'account_id' => 'ID',
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
                            ]
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertInstanceOf(ConfigurationInterface::class, $this->configuration);
        $this->assertInstanceOf(TreeBuilder::class, $treeBuilder);
        $this->assertEquals([
            'access_token' => 'token',
            'account_id' => 'ID',
            'default_signer_name' => 'Grégoire Hébert',
            'default_signer_email' => 'gregoire@les-tilleuls.coop',
            'api_uri' => 'https://demo.docusign.net/restapi',
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
                        ]
                    ],
                ],
            ],
        ], $config);
    }
}
