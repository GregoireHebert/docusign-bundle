<?php

declare(strict_types=1);

namespace DocusignBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Webmozart\Assert\Assert;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('docusign');

        $rootNode
            ->children()
                ->scalarNode('accessToken')
                    ->info('Obtain an OAuth access token from https://developers.hqtest.tst/oauth-token-generator.')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('accountId')
                    ->info('Obtain your accountId from demo.docusign.com The account id is shown in the drop down on the upper right corner of the screen by your picture or the default picture.')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('defaultSignerName')
                    ->info('Recipient Information as the signer full name')
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('defaultSignerEmail')
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
                ->scalarNode('apiURI')
                    ->info('Docusign API URI (uses the demo by default)')
                    ->cannotBeEmpty()
                    ->defaultValue('https://demo.docusign.net/restapi')
                ->end()
                ->scalarNode('callbackRouteName')
                    ->info('Where does Docusign redirect the user after the document has been signed. Use a Route name.')
                    ->cannotBeEmpty()
                    ->defaultValue('docusign_callback')
                ->end()
                ->scalarNode('webHookRouteName')
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
                    ->useAttributeAsKey('documentName')
                    ->arrayPrototype()
                        ->performNoDeepMerging()
                        ->children()
                            ->arrayNode('signatures')
                                ->info('Document signatures')
                                ->arrayPrototype()
                                    ->performNoDeepMerging()
                                    ->children()
                                        ->scalarNode('page')->isRequired()->info('Page number where to apply the signature. (default: 1)')->defaultValue(1)->end()
                                        ->scalarNode('xPosition')->isRequired()->info('X position of the signature (top left corner)')->end()
                                        ->scalarNode('yPosition')->isRequired()->info('Y position of the signature (top left corner)')->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                    ->defaultValue([])
                ->end()
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
                ->end()
            ->end();

        return $treeBuilder;
    }
}
