<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\Assert\Assert;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $treeBuilder = new TreeBuilder('docusign');
            $rootNode = $treeBuilder->getRootNode();
        } else {
            $treeBuilder = new TreeBuilder();
            $rootNode = $treeBuilder->root('docusign');
        }

        $rootNode
            ->children()
                ->scalarNode('access_token')
                    ->info('Obtain an OAuth access token from https://developers.hqtest.tst/oauth-token-generator.')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('account_id')
                    ->info('Obtain your accountId from demo.docusign.com The account id is shown in the drop down on the upper right corner of the screen by your picture or the default picture.')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_signer_name')
                    ->info('Recipient Information as the signer full name')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('default_signer_email')
                    ->info('Recipient Information as the signer email')
                    ->cannotBeEmpty()
                    ->validate()
                        ->ifTrue(static function ($v) {
                            try {
                                Assert::email($v);

                                return false;
                            } catch (\Exception $e) {
                                return true;
                            }
                        })
                        ->thenInvalid('Invalid email %s')
                    ->end()
                ->end()
                ->scalarNode('api_uri')
                    ->info('Docusign API URI (uses the demo by default)')
                    ->cannotBeEmpty()
                    ->defaultValue('https://demo.docusign.net/restapi')
                ->end()
                ->scalarNode('callback_route_name')
                    ->info('Where does Docusign redirect the user after the document has been signed. Use a Route name.')
                    ->cannotBeEmpty()
                    ->defaultValue('docusign_callback')
                ->end()
                ->scalarNode('webhook_route_name')
                    ->info('Where does Docusign send the event notifications during the signature. Use a Route name.')
                    ->cannotBeEmpty()
                    ->defaultValue('docusign_webhook')
                ->end()
                ->booleanNode('signatures_overridable')
                    ->info('Let the user override the signature position through the request.')
                    ->defaultFalse()
                ->end()
                ->arrayNode('signatures')
                    ->info('Position the signatures on a page, X and Y axis of your documents.')
                    ->useAttributeAsKey('document_name')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->arrayNode('signatures')
                                ->info('Document signatures')
                                ->arrayPrototype()
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('page')->isRequired()->info('Page number where to apply the signature. (default: 1)')->defaultValue(1)->end()
                                        ->scalarNode('x_position')->isRequired()->info('X position of the signature (top left corner)')->end()
                                        ->scalarNode('y_position')->isRequired()->info('Y position of the signature (top left corner)')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
            ->end();

        $this->addStorageCompat($rootNode);

        return $treeBuilder;
    }

    /*
     * Add compatibility for flysystem in symfony 3.4
     */
    private function addStorageCompat(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
                ->arrayNode('storages')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->end()
                            ->arrayNode('options')
                                ->variablePrototype()
                            ->end()
                            ->defaultValue([])
                        ->end()
                        ->scalarNode('visibility')->defaultNull()->end()
                        ->booleanNode('case_sensitive')->defaultTrue()->end()
                        ->booleanNode('disable_asserts')->defaultFalse()->end()
                    ->end()
                ->end()
                ->defaultValue([])
            ->end();
    }
}
