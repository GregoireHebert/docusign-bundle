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

use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('docusign');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->beforeNormalization()
                ->ifTrue(static function ($v) { return \is_string($v['mode'] ?? null); })
                ->then(static function ($v) { return ['default' => $v]; })
            ->end()
            ->useAttributeAsKey('name')
            ->arrayPrototype()
                ->validate()
                    ->ifTrue(function ($v) {
                        return \in_array($v['mode'] ?? null, [EnvelopeBuilder::MODE_EMBEDDED, EnvelopeBuilder::MODE_REMOTE], true) && !\array_key_exists('account_id', $v);
                    })
                    ->then(function ($v): void {
                        throw new InvalidConfigurationException("The child node \"account_id\" must be configured on \"$v[mode]\" mode.");
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return \in_array($v['mode'] ?? null, [EnvelopeBuilder::MODE_EMBEDDED, EnvelopeBuilder::MODE_REMOTE], true) && !\array_key_exists('sign_path', $v);
                    })
                    ->then(function ($v): void {
                        throw new InvalidConfigurationException("The child node \"sign_path\" must be configured on \"$v[mode]\" mode.");
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return \in_array($v['mode'] ?? null, [EnvelopeBuilder::MODE_EMBEDDED, EnvelopeBuilder::MODE_REMOTE], true) && !\array_key_exists('auth_jwt', $v);
                    })
                    ->then(function ($v): void {
                        throw new InvalidConfigurationException("The child node \"auth_jwt\" must be configured on \"$v[mode]\" mode.");
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return \in_array($v['mode'] ?? null, [EnvelopeBuilder::MODE_EMBEDDED, EnvelopeBuilder::MODE_REMOTE], true) && !\array_key_exists('storage', $v);
                    })
                    ->then(function ($v): void {
                        throw new InvalidConfigurationException("The child node \"storage\" must be configured on \"$v[mode]\" mode.");
                    })
                ->end()
                ->validate()
                    ->ifTrue(function ($v) {
                        return EnvelopeBuilder::MODE_CLICKWRAP === ($v['mode'] ?? null) && !\array_key_exists('auth_clickwrap', $v);
                    })
                    ->then(function ($v): void {
                        throw new InvalidConfigurationException('The child node "auth_clickwrap" must be configured on "clickwrap" mode.');
                    })
                ->end()
                ->children()
                    ->enumNode('mode')
                        ->values([EnvelopeBuilder::MODE_EMBEDDED, EnvelopeBuilder::MODE_REMOTE, EnvelopeBuilder::MODE_CLICKWRAP])
                        ->info('Type of signature to use: remote or embedded.')
                        ->cannotBeEmpty()
                        ->isRequired()
                    ->end()
                    ->booleanNode('demo')
                        ->info('Enable the demo mode')
                        ->defaultFalse()
                    ->end()
                    ->booleanNode('enable_profiler')
                        ->info('Enable the Symfony Profiler')
                        ->defaultFalse()
                    ->end()
                    ->scalarNode('account_id')
                        ->info('Obtain your accountId from DocuSign: the account id is shown in the drop down on the upper right corner of the screen by your picture or the default picture')
                        // isRequired is done dynamically through global validate method
                    ->end()
                    ->scalarNode('default_signer_name')
                        ->info('Recipient Information as the signer full name')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('default_signer_email')
                        ->info('Recipient Information as the signer email')
                        ->cannotBeEmpty()
                    ->end()
                    ->scalarNode('api_uri')
                        ->info('DocuSign production API URI (default: https://www.docusign.net/restapi)')
                        ->cannotBeEmpty()
                        ->defaultValue('https://www.docusign.net/restapi')
                    ->end()
                    ->scalarNode('callback')
                        ->info('Where does DocuSign redirect the user after the document has been signed. Use a route name')
                        ->cannotBeEmpty()
                        ->defaultValue('docusign_callback')
                    ->end()
                    ->scalarNode('sign_path')
                        ->info('The url of the sign process.')
                        // isRequired is done dynamically through global validate method
                    ->end()
                    ->booleanNode('signatures_overridable')
                        ->info('Let the user override the signature position through the request')
                        ->defaultFalse()
                    ->end()
                    ->arrayNode('signatures')
                        ->info('Position the signatures on a page, X and Y axis of your documents')
                        ->beforeNormalization()
                            ->ifTrue(static function ($v) { return isset($v[0]); })
                            ->then(static function ($v) { return ['default' => [$v[0]]]; })
                        ->end()
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
                        // isRequired is done dynamically through global validate method
                        ->info('Configure JSON Web Token (JWT) authentication: https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken')
                        ->children()
                            ->scalarNode('private_key')->isRequired()->info('Path to the private RSA key generated by DocuSign')->end()
                            ->scalarNode('integration_key')
                                ->isRequired()
                                ->info('To generate your integration key, follow this documentation: https://developers.docusign.com/esign-soap-api/reference/Introduction-Changes/Integration-Keys')
                            ->end()
                            ->scalarNode('user_guid')
                                ->isRequired()
                                ->info('Obtain your user UID (also called API username) from DocuSign Admin > Users > User > Actions > Edit')
                            ->end()
                            ->integerNode('ttl')->defaultValue(3600)->info('Token TTL in seconds (default: 3600)')->end()
                            ->enumNode('grant_type')
                                ->values(['authorization_code', 'implicit'])
                                ->info('Grant type to use: authorization_code or implicit.')
                                ->cannotBeEmpty()
                                ->defaultValue('authorization_code')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('auth_clickwrap')
                        // isRequired is done dynamically through global validate method
                        ->info('Configure Clickwrap: https://support.docusign.com/en/guides/click-user-guide')
                        ->children()
                            ->scalarNode('user_guid')
                                ->isRequired()
                                ->info('Obtain your user UID (also called API username) from DocuSign Admin > Users > User > Actions > Edit')
                            ->end()
                            ->scalarNode('api_account_id')
                                ->isRequired()
                                ->info('The API Account ID from DocuSign Admin')
                            ->end()
                            ->scalarNode('clickwrap_id')
                                ->isRequired()
                                ->info('The Clickwrap ID from DocuSign Manage > Clickwraps')
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('storage')
                        // isRequired is done dynamically through global validate method
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
