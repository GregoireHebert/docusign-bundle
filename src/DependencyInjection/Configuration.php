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

namespace DocusignBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Compiler\ValidateEnvPlaceholdersPass;
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
            ->beforeNormalization()
                ->ifTrue(static function ($v) { return \is_bool($v['demo'] ?? null); })
                ->then(static function ($v) { return ['default' => $v]; })
            ->end()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->children()
                    ->booleanNode('demo')
                        ->info('Enable the demo mode')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('account_id')
                        ->info('Obtain your accountId from DocuSign: the account id is shown in the drop down on the upper right corner of the screen by your picture or the default picture')
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(static function ($v) {
                                // BC compat for symfony < 4.1
                                if (!class_exists(ValidateEnvPlaceholdersPass::class)) {
                                    return true;
                                }

                                try {
                                    Assert::integer($v);
                                    Assert::true(7 === \strlen((string) $v));

                                    return false;
                                } catch (\Exception $e) {
                                    return true;
                                }
                            })
                            ->thenInvalid('Invalid account id %s')
                        ->end()
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
                                // BC compat for symfony < 4.1
                                if (!class_exists(ValidateEnvPlaceholdersPass::class)) {
                                    return true;
                                }

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
                        ->info('DocuSign production API URI (default: https://www.docusign.net/restapi)')
                        ->cannotBeEmpty()
                        ->defaultValue('https://www.docusign.net/restapi')
                    ->end()
                    ->scalarNode('callback_route_name')
                        ->info('Where does DocuSign redirect the user after the document has been signed. Use a route name')
                        ->cannotBeEmpty()
                        ->defaultValue('docusign_callback')
                    ->end()
                    ->booleanNode('signatures_overridable')
                        ->info('Let the user override the signature position through the request')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('signatures')
                        ->info('Position the signatures on a page, X and Y axis of your documents')
                        ->useAttributeAsKey('document_name')
                        ->arrayPrototype()
                            ->performNoDeepMerging()
                            ->arrayPrototype()
                                ->performNoDeepMerging()
                                ->children()
                                    ->scalarNode('page')->isRequired()->info('Page number where to apply the signature (default: 1)')->defaultValue(1)->end()
                                    ->scalarNode('x_position')->isRequired()->info('X position of the signature (top left corner)')->end()
                                    ->scalarNode('y_position')->isRequired()->info('Y position of the signature (top left corner)')->end()
                                ->end()
                            ->end()
                        ->end()
                        ->defaultValue([])
                    ->end()
                    ->arrayNode('auth_jwt')
                        ->isRequired()
                        ->info('Configure JSON Web Token (JWT) authentication: https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken')
                        ->children()
                            ->scalarNode('private_key')->isRequired()->info('Path to the private RSA key generated by DocuSign')->end()
                            ->scalarNode('integration_key')
                                ->isRequired()
                                ->info('To generate your integration key, follow this documentation: https://developers.docusign.com/esign-soap-api/reference/Introduction-Changes/Integration-Keys')
                                ->validate()
                                    ->ifTrue(static function ($v) {
                                        // BC compat for symfony < 4.1
                                        if (!class_exists(ValidateEnvPlaceholdersPass::class)) {
                                            return true;
                                        }

                                        try {
                                            Assert::uuid($v);

                                            return false;
                                        } catch (\Exception $e) {
                                            return true;
                                        }
                                    })
                                    ->thenInvalid('Invalid integration key %s')
                                ->end()
                            ->end()
                            ->scalarNode('user_guid')
                                ->isRequired()
                                ->info('Obtain your user UID (also called API username) from DocuSign Admin > Users > User > Actions > Edit')
                                ->validate()
                                    ->ifTrue(static function ($v) {
                                        // BC compat for symfony < 4.1
                                        if (!class_exists(ValidateEnvPlaceholdersPass::class)) {
                                            return true;
                                        }

                                        try {
                                            Assert::uuid($v);

                                            return false;
                                        } catch (\Exception $e) {
                                            return true;
                                        }
                                    })
                                    ->thenInvalid('Invalid user guid %s')
                                ->end()
                            ->end()
                            ->integerNode('ttl')->defaultValue(3600)->info('Token TTL in seconds (default: 3600)')->end()
                        ->end()
                    ->end()
                    ->arrayNode('storage')
                        ->beforeNormalization()
                            ->ifString()
                            ->then(static function ($v) { return ['storage' => $v]; })
                        ->end()
                        ->children()
                            ->scalarNode('adapter')->end()
                            ->arrayNode('options')
                                ->variablePrototype()
                            ->end()
                        ->end()
                        ->scalarNode('storage')->end()
                        ->scalarNode('visibility')->defaultNull()->end()
                        ->booleanNode('case_sensitive')->defaultTrue()->end()
                        ->booleanNode('disable_asserts')->defaultFalse()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
