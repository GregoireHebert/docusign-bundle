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
        $treeBuilder = new TreeBuilder('docusign');
        $rootNode = $treeBuilder->getRootNode();

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
            ->end();

        return $treeBuilder;
    }
}
