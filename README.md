# DocuSign Bundle

[![Actions Status](https://github.com/gregoirehebert/docusign-bundle/workflows/CI/badge.svg)](https://github.com/gregoirehebert/docusign-bundle/actions)
[![Packagist Version](https://img.shields.io/packagist/v/gheb/docusign-bundle.svg?style=flat-square)](https://packagist.org/packages/gheb/docusign-bundle)
[![Software license](https://img.shields.io/github/license/gregoirehebert/docusign-bundle.svg?style=flat-square)](https://github.com/gregoirehebert/docusign-bundle/blob/master/LICENSE)

This Bundle is used to create electronic signature with DocuSign.
At the moment it only does handle implicit authentication with embedded signature.
That means, that you need an account on DocuSign, and you'll be redirected to sign the document.

DocuSign also offers the possibility to sign remotely, or to trigger a simple agreement click.
But these options are not available yet.
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
    defaultSignerName: "Grégoire Hébert"
    defaultSignerName: "gregoire@les-tilleuls.coop"
    apiURI: "https://demo.docusign.net/restapi" # default
    callbackRouteName: "docusign_callback"
    webHookRouteName: "docusign_webhook"
    signature_overridable: false # default
    signatures:
        defaultDocumentType:
            signatures:
                -
                    page: 1 # default
                    xPosition: 200 # top left corner in pixels
                    yPosition: 400 # top left corner in pixels
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

You'll get redirected to DocuSign website.
DocuSign will redirect you to `docusign_callback` : `/docusign/callback/{envelopeId}`
DocuSign will also send the result to `docusign_webhook` : `/docusign/webhook`

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
                -
                    page: 1 # default
                    xPosition: 200 # top left corner in pixels
                    yPosition: 400 # top left corner in pixels
        otherDocumentType:
            signatures:
                -
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

## Events

### Sign events

When signing a document, DocuSign sends the user back to your application to the callback route.
But usually you need to send custom data to that route. To do that you can subscribe to the `PreSignEvent`.

Even more useful, when DocuSign redirect the user to the callback route, you might need to grab some data from the Request and modify the Response sent. You could even replace the Response object.
To do that you can subscribe to the `DocumentSignatureCompletedEvent`.


```php
// src/EventSubscriber/PreSignSubscriber.php
namespace App\EventSubscriber;

use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\Events\PreSignEvent;
use DocusignBundle\Events\DocumentSignatureCompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PreSignSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return [
            PreSignEvent::class => 'preSign'
            DocumentSignatureCompletedEvent::class => 'onDocumentSignatureCompleted'
        ];
    }

    public function preSign(PreSignEvent $preSign)
    {
        // Here you can add the parameters you want to be sent back to you by DocuSign in the callback.
        $envelopeBuilder = $preSign->getEnvelopeBuilder();

        // $envelopeBuilder->addCallbackParameter([]);
        // $envelopeBuilder->setCallbackParameters();
        // ...
    }

    public function onDocumentSignatureCompleted(DocumentSignatureCompletedEvent $documentSignatureCompleted)
    {
        $request = $documentSignatureCompleted->getRequest();
        $response = $documentSignatureCompleted->getResponse();

        // ... do whatever you want with the response.
    }
}
```

## Backward Compatibility promise

This library follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
