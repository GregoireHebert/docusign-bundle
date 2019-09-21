# Docusign

[![Packagist Version](https://img.shields.io/packagist/v/gheb/docusign-bundle.svg?style=flat-square)](https://packagist.org/packages/gheb/docusign-bundle)
[![Software license](https://img.shields.io/github/license/gregoirehebert/docusign-bundle.svg?style=flat-square)](https://github.com/gregoirehebert/docusign-bundle/blob/master/LICENSE)

This Bundle is used to create electronic signature with DocuSign.
At the moment it only does handle implicit authentication with embedded signature.
Feel free to contribute :)

This bundle is coupled with [FlySystem](https://flysystem.thephpleague.com) to handle the files.
This bundle copy the same API/structure as the related bundle offers.
I did a copy because of the 3.4 limitation.

Indeed the flysystem-bundle relies on [named aliases](https://symfony.com/doc/current/service_container/autowiring.html#dealing-with-multiple-implementations-of-the-same-type)
(introduced in Symfony 4.2) in order to create and configure multiple filesystems while still
following the best practices of software architecture (SOLID principles).

## Install

```shell
$ composer require gheb/docusign-bundle
```

### register the bundle

```php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new \DocusignBundle\DocusignBundle(),
        ];
    }
}
```

### Import routing

```yml
# app/config/routing.yml

docusign:
    resource: '@DocusignBundle/Resources/config/routing.yml'
```

### Configure the bundle

Check the [official documentation](https://github.com/docusign/qs-php).
[Get your testing access token](https://developers.docusign.com/oauth-token-generator).
Your account id is visible on the top right level of your demo.docusign account right below your profile picture in the little drop-down.

```yml
# app/config/config.yml

docusign:
    accessToken: "YourAccessToken"
    accountId: "yourAccountId"
    signerName: "Grégoire Hébert"
    signerEmail: "gregoire@les-tilleuls.coop"
    apiURI: "docusign.com/API/URI"
    callBackRouteName: "docusign_callback"
    webHookRouteName: "docusign_webhook"
    storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

### Configure a custom storage

To access the document, we use the [`league/flysystem`](https://flysystem.thephpleague.com) library.

Create a class that implements the `League\Flysystem\FilesystemInterface` interface.
Now you can specify as adapter your class.

```yml
# app/config/config.yml

docusign:
    # ...
    storages:
        docusign.storage:
            adapter: 'App\Your\Class'
```

### Add a missing storage but already supported by the flysystem bundle

At the moment only the `local` has been ported. If you need to import one of the adapter existing in [here](https://github.com/thephpleague/flysystem-bundle/tree/master/src/Adapter/Builder).
You can either ask me, or open a PR with just a copy of the one from the bundle, paste it in the `src/Adapter/Builder` directory, and add it in the `Docusign\Adapter\AdapterDefinitionFactory::__construct`.
I'll be glad to merge it :)


## Basic usage

*GET* `docusign` : `/docusign?path={document_path}`

You'll get redirected to docusign website.
Docusign will redirect you to `docusign_callback` : `/docusign/callback`
Docusign will also send the result to `docusign_webhook` : `/docusign/webhook`

If you need to override one, it's the callback one. :)


## Backward Compatibility promise

This library follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
