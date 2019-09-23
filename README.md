# Docusign

[![Actions Status](https://github.com/gregoirehebert/docusign-bundle/workflows/CI/badge.svg)](https://github.com/gregoirehebert/docusign-bundle/actions)
[![Packagist Version](https://img.shields.io/packagist/v/gheb/docusign-bundle.svg?style=flat-square)](https://packagist.org/packages/gheb/docusign-bundle)
[![Software license](https://img.shields.io/github/license/gregoirehebert/docusign-bundle.svg?style=flat-square)](https://github.com/gregoirehebert/docusign-bundle/blob/master/LICENSE)

This Bundle is used to create electronic signature with DocuSign.
At the moment it only does handle implicit authentication with embedded signature.
That means, that you need an account on docusign, and you'll be redirected to sign the document.

Docusign also offers the possibility to sign remotely, or to trigger a simple agreement click.
But these options are not available yet.
Feel free to contribute :)

This bundle is coupled with [FlySystem](https://flysystem.thephpleague.com) and [FlySystem Bundle](https://github.com/thephpleague/flysystem-bundle) to handle the files.

## Install

```shell
$ composer require gheb/docusign-bundle
```

### register the bundle

```php
//config/bundles.php
return [
    DocusignBundle\DocusignBundle::class => ['all' => true],
]
```

### Import routing

```yml
# config/routes.yml

docusign:
    resource: '@DocusignBundle/Resources/config/routing.yml'
```

### Configure the bundle

Check the [official documentation](https://github.com/docusign/qs-php).
[Get your testing access token](https://developers.docusign.com/oauth-token-generator).
Your account id is visible on the top right level of your demo.docusign account right below your profile picture in the little drop-down.

```yml
# config/packages/docusign.yml

docusign:
    accessToken: "YourAccessToken"
    accountId: "yourAccountId"
    signerName: "Grégoire Hébert"
    signerEmail: "gregoire@les-tilleuls.coop"
    apiURI: "https://demo.docusign.net/restapi" # default
    callBackRouteName: "docusign_callback"
    webHookRouteName: "docusign_webhook"
    signature_overridable: false # default
    signatures:
        defaultDocumentType:
            signatures:
                page: 1 # default
                xPosition: 200 # top left corner in pixels
                yPosition: 400 # top left corner in pixels
```

### Configure the storage
```yml
# config/packages/flysystem.yml
flysystem:
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
# config/packages/flysystem.yml
flysystem:
    # ...
    storages:
        docusign.storage:
            adapter: 'App\Your\Class'
```


## Basic usage

*GET* `docusign` : `/docusign?path={document_path}`

You'll get redirected to docusign website.
Docusign will redirect you to `docusign_callback` : `/docusign/callback/{envelopeId}`
Docusign will also send the result to `docusign_webhook` : `/docusign/webhook`

## Document type

You can define different type of document, so you can place your signatures at different places.
By default, if you've go only one type defined, you don't need to specify anything.
But from the moment you've got two, there is an ambiguity. You need to select which one to use.

```yml
# app/config/config.yml

docusign:
    # ...
    signatures:
        defaultDocumentType:
            signatures:
                page: 1 # default
                xPosition: 200 # top left corner in pixels
                yPosition: 400 # top left corner in pixels
        otherDocumentType:
            signatures:
                page: 2
                xPosition: 500 # top left corner in pixels
                yPosition: 800 # top left corner in pixels
    # ...
```

*GET* `docusign` : `/docusign?path={document_path}&documentType=otherDocumentType`

## Override configuration signature positions

If you want to set a one time, or let your user define where to apply the signature.
Set the configuration `signatures_overridable` option to true.

```yml
# app/config/config.yml

docusign:
    # ...
    signatures_overridable: true
    # ...
```

Then add a signature array to your query.

```php
$signatures = ['signatures'=>[['page'=>1,'xPosition'=>200,'yPosition'=>400]]]
$parameters = url_decode(http_build_query($a)); // signatures[0][page]=1&signatures[0][xPosition]=200&signatures[0][yPosition]=400`
```

*GET* `docusign` : `/docusign?path={document_path}&signatures[0][page]=1&signatures[0][xPosition]=200&signatures[0][yPosition]=400`

It will override the signatures configured.

## Backward Compatibility promise

This library follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
