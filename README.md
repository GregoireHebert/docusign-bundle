# DocuSign Bundle

[![Actions Status](https://github.com/gregoirehebert/docusign-bundle/workflows/CI/badge.svg)](https://github.com/gregoirehebert/docusign-bundle/actions)
[![Packagist Version](https://img.shields.io/packagist/v/gheb/docusign-bundle.svg?style=flat-square)](https://packagist.org/packages/gheb/docusign-bundle)
[![Software license](https://img.shields.io/github/license/gregoirehebert/docusign-bundle.svg?style=flat-square)](https://github.com/gregoirehebert/docusign-bundle/blob/master/LICENSE)
[![Coverage Status](https://coveralls.io/repos/github/GregoireHebert/docusign-bundle/badge.svg?branch=50/merge)](https://coveralls.io/github/GregoireHebert/docusign-bundle?branch=50/merge)

This Symfony Bundle is used to create electronic signature with DocuSign.
An Electronic Signature ensure a person agreed with a document.

This bundle is coupled with [FlySystem](https://flysystem.thephpleague.com) and can be with [FlySystem Bundle](https://github.com/thephpleague/flysystem-bundle) to handle the files.

## Requirements

- php ^7.2api
- simplexml php extension
- curl php extension

## Table of Content

1.  [Bundle Installation](doc/installation.md)
    1.  [Register the bundle](doc/installation.md#register-the-bundle)
1.  [Configure DocuSign](doc/configure-docusign.md#docusign-configuration)
    1.  [Types of signatures](doc/configure-docusign.md#types-of-signatures)
    1.  [Format restrictions:](doc/configure-docusign.md#format-restrictions)
    1.  [Add an integration key on DocuSign](doc/configure-docusign.md#add-an-integration-key-on-docusign)
1.  [Configure the bundle](doc/configure-the-bundle.md)
    1.  [Create a default signature configuration](doc/configure-the-bundle.md#create-a-default-signature-configuration)
    1.  [Testing configuration](doc/configure-the-bundle.md#testing-configuration)
    1.  [Document variations](doc/configure-the-bundle.md#document-variations)
    1.  [Multiple signature configuration](doc/configure-the-bundle.md#multiple-signature-configuration)
1.  [Basic usage](doc/usage.md)
    1.  [JWT consent agreement](doc/usage.md#jwt-consent-agreement)
    1.  [Inside a twig template](doc/usage.md#inside-a-twig-template)
    1.  [Events](doc/events.md)
        1.  [Sign events](doc/events.md#sign-events)
        1.  [WebHook events](doc/events.md#webhook-events)
1.  [Customization](doc/customize-the-bundle.md)
    1. [Use the demo for testing](doc/customize-the-bundle.md#use-the-demo-for-testing)
    1. [Configure a custom storage](doc/customize-the-bundle.md#configure-a-custom-storage)
    1. [Override configuration signature positions](doc/customize-the-bundle.md#override-configuration-signature-positions)
    1. [Using an external route as callback](doc/customize-the-bundle.md#using-an-external-route-as-callback)
1.  [Use league/flysystem-bundle](doc/use-flysystem-bundle.md)
    1.  [Installation](doc/use-flysystem-bundle.md#installation)
    1.  [Configure the storage](doc/use-flysystem-bundle.md#configure-the-storage)
    1.  [Configure the signature](doc/use-flysystem-bundle.md#configure-the-signature)

## Code of conduct

This bundle is ruled by a [code a conduct](/.github/CODE_OF_CONDUCT.md).

## Contributing

Please have a look to [the contributing guide](/.github/CONTRIBUTING.md).

## Backward Compatibility promise

This bundle follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
