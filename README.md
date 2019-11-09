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

This bundle is coupled with [FlySystem](https://flysystem.thephpleague.com) and [FlySystem Bundle](https://github.com/thephpleague/flysystem-bundle) to handle the files.

## Install

```shell
$ composer require gheb/docusign-bundle
```

### Optional

```shell
$ composer require league/flysystem-bundle
```

### register the bundle

Symfony 3.4

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

Symfony 4+

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
    resource: '@DocusignBundle/Resources/config/routing.xml'
```

### Configure the bundle

Check the [official documentation](https://github.com/docusign/qs-php).

Your account id is visible on the top right level of your DocuSign account right below your profile picture in the
little drop-down.

[Configure JWT Grant](https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken) and set
your private key into `%kernel.project_dir%/var/jwt/docusign.pem`. This path is configurable as following:

```yml
# config/packages/docusign.yml

docusign:
    demo: "%kernel.debug"
    jwt:
        private_key: "%kernel.project_dir%/var/jwt/docusign.pem"
        integration_key: "yourIntegrationKey"
        user_guid: "yourUserGuid"
    account_id: "yourAccountId"
    default_signer_name: "Grégoire Hébert"
    default_signer_email: "gregoire@les-tilleuls.coop"
    api_uri: "https://www.docusign.net/restapi" # default
    callback_route_name: "docusign_callback"
    signatures_overridable: false # default
    signatures:
        default_document_type:
            signatures:
                -
                    page: 1 # default
                    x_position: 200 # top left corner in pixels
                    y_position: 400 # top left corner in pixels

    # if you do not use league/flysystem-bundle
    storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

### Configure the storage with league/flysystem-bundle
```yml
# config/packages/flysystem.yml
flysystem:
   storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

### Configure a custom storage without league/flysystem-bundle

To access the document, we use the [`league/flysystem`](https://flysystem.thephpleague.com) library.

Create a class that implements the `League\Flysystem\FilesystemInterface` interface.
Now you can specify your class as adapter.

```yml
# app/config/config.yml

docusign:
    # ...
    storages:
        docusign.storage:
            adapter: 'App\Your\Class'
```

### Add a missing storage without league/flysystem-bundle but already supported by the league/flysystem-bundle

At the moment only the `local` has been ported. If you need to import one of the adapter existing in [here](https://github.com/thephpleague/flysystem-bundle/tree/master/src/Adapter/Builder).
You can either ask me, or open a PR with just a copy of the one from the bundle, paste it in the `src/Adapter/Builder` directory, and add it in the `Docusign\Adapter\AdapterDefinitionFactory::__construct`.
I'll be glad to merge it :)

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

You'll get redirected to DocuSign website.
DocuSign will redirect you to `docusign_callback` : `/docusign/callback`
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
        default_document_type:
            signatures:
                -
                    page: 1 # default
                    x_position: 200 # top left corner in pixels
                    y_position: 400 # top left corner in pixels
        other_document_type:
            signatures:
                -
                    page: 2
                    x_position: 500 # top left corner in pixels
                    y_position: 800 # top left corner in pixels
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
$signatures = ['signatures'=>[['page'=>1,'x_position'=>200,'y_position'=>400]]]
$parameters = url_decode(http_build_query($a)); // signatures[0][page]=1&signatures[0][x_position]=200&signatures[0][y_position]=400`
```

*GET* `docusign` : `/docusign?path={document_path}&signatures[0][page]=1&signatures[0][x_position]=200&signatures[0][y_position]=400`

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

class PreSignSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return [
            PreSignEvent::class => 'preSign',
            DocumentSignatureCompletedEvent::class => 'onDocumentSignatureCompleted'
        ];
    }

    public function preSign(PreSignEvent $preSign)
    {
        // Here you can add the parameters you want to be sent back to you by DocuSign in the callback.
        $envelopeBuilder = $preSign->getEnvelopeBuilder();
        $request = $preSign->getRequest();

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

### Webhook events

DocuSign calls a Webhook on your project, allowing you to handle the signed document, its status, etc.

**Your Webhook url MUST BE in HTTPS.**

Depending on the document status, several events are available, giving you access to the DocuSign request (as a
\SimpleXMLElement) and the request.

```php
// src/EventSubscriber/WebhookSubscriber.php
namespace App\EventSubscriber;

use DocusignBundle\Events\AuthenticationFailedEvent;
use DocusignBundle\Events\AutoRespondedEvent;
use DocusignBundle\Events\CompletedEvent;
use DocusignBundle\Events\DeclinedEvent;
use DocusignBundle\Events\DeliveredEvent;
use DocusignBundle\Events\SentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class WebhookSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        // return the subscribed events, their methods and priorities
        return [
            AuthenticationFailedEvent::class => 'onAuthenticationFailed',
            AutoRespondedEvent::class => 'onAutoResponded',
            CompletedEvent::class => 'onCompleted',
            DeclinedEvent::class => 'onDeclined',
            DeliveredEvent::class => 'onDelivered',
            SentEvent::class => 'onSent',
        ];
    }

    // ...
}
```

## Backward Compatibility promise

This library follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
