# DocuSign Bundle

[![Actions Status](https://github.com/gregoirehebert/docusign-bundle/workflows/CI/badge.svg)](https://github.com/gregoirehebert/docusign-bundle/actions)
[![Packagist Version](https://img.shields.io/packagist/v/gheb/docusign-bundle.svg?style=flat-square)](https://packagist.org/packages/gheb/docusign-bundle)
[![Software license](https://img.shields.io/github/license/gregoirehebert/docusign-bundle.svg?style=flat-square)](https://github.com/gregoirehebert/docusign-bundle/blob/master/LICENSE)

This Symfony Bundle is used to create electronic signature with DocuSign.
An Electronic Signature ensure a person agreed with the document.

1.  [Requirements](#requirements)
1.  [Bundle Installation](#bundle-installation)
    1.  [register the bundle](#register-the-bundle)
    1.  [Configure the bundle](#configure-the-bundle)
1.  [Docusign Configurationn](#docusign-configurationn)
    1.  [Types of signatures](#types-of-signatures)
    1.  [Format Restrictions:](#format-restrictions)
    1.  [Add an integration key on docusign](#add-an-integration-key-on-docusign)
1.  [Basic usage](#basic-usage)
    1.  [Inside a twig template:](#inside-a-twig-template:)
    1.  [Using document variations signature positionning](#using-document-variations-signature-positionning)
1.  [Customization](#customization)
    1.  [Configure a custom storage without league/flysystem-bundle](#configure-a-custom-storage-without-league/flysystem-bundle)
    1.  [Use league/flysystem-bundle](#use-league/flysystem-bundle)
        1[  (Installation](#installation)
        1[  (Configure the storage](#configure-the-storage)
    1.  [Override configuration signature positions](#override-configuration-signature-positions)
    1.  [Events](#events)
        1[  (Sign events](#sign-events)
        1[  (Webhook events](#webhook-events)
    1.  [Using an external route as callback](#using-an-external-route-as-callback)
1.  [Backward Compatibility promise](#backward-compatibility-promise)

This bundle is coupled with [FlySystem](https://flysystem.thephpleague.com) and [FlySystem Bundle](https://github.com/thephpleague/flysystem-bundle) to handle the files.

## Requirements

- php ^7.2
- simplexml php extension
- curl php extension

## Bundle Installation

```shell
$ composer require gheb/docusign-bundle
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

### Configure the bundle

Check the [official documentation](https://github.com/docusign/qs-php).

[Configure JWT Grant](https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken) and set
your private key into `%kernel.project_dir%/var/jwt/docusign.pem`. This path is configurable as following:

```yml
# config/packages/docusign.yml

docusign:
    my_remote_signature: # Name of the signature configuration
        mode: remote # Mode used to sign
        demo: %kernel.debug%  # Default value: false

        auth_jwt:
            private_key: "YourPrivateKey"
            integration_id: "YourIntegrationId"
            user_guid: "YourUserId"

        default_signer_name: "Grégoire Hébert" # Name of the person that will be notified and will sign the document if none is sent to the url.
        default_signer_email: "gregoire@les-tilleuls.coop" # Mail of the person that will be notified and will sign the document if none is sent to the url.

        api_uri: "https://www.docusign.net/restapi" # Docusign api uri

        callback: "docusign_callback" # Your route where to redirect the user after signature

        # To sign you need to generate a route to call.
        # The route name will be formated `docusign.sign.{signaturename}` for this one it will be `docusign.sign.my_embedded_signature` and will have `my_embedded_signature` as attribute type
        # By defining this, you have a full control over the security applied to this route. see https://symfony.com/doc/current/security/access_control.html
        sign_path: '/my/embedded/sign/path'

        signatures_overridable: false # When set to true, you can define the pages and positions through the url as query parameters.

        # Where to position the signature
        signatures:
            # this is an array of positions, you can have multiple signatures locations per document and pages
            -
                page: 1 # Default
                x_position: 200 # Top left corner in pixels
                y_position: 400 # Top left corner in pixels

        # Define the storage to use to get the file to sign
        storage: 'docusign.storage' # if you set a string, it will look for an already defined storage. It could be defined with league/flysystem-bundle or by another signature definition when the storage key is an array of values

        # when the storage key is an array it will declare a new storage as if it was using the league/flysystem-bundle it's name will be `docusign.storage.my_remote_signature`
        # Do not use this format you do have `league/flysystem-bundle` installed, use the bundle configuration.
        storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'

    my_embedded_signature:
        mode: embedded
        demo: %kernel.debug%

        auth_authorization_code:
            integration_id: "YourIntegrationId"
            user_guid: "YourUserId"

        account_id: "yourAccountId" # Your account id is visible on the top right level of your developper DocuSign account right below your profile picture in the little drop-down.
        default_signer_name: "Grégoire Hébert"
        default_signer_email: "gregoire@les-tilleuls.coop"

        api_uri: "https://www.docusign.net/restapi" # default

        callback: "docusign_callback"
        sign_path: '/my/embedded/sign/path/{document_type}'

        signatures_overridable: false # default

        # Where to position the signatures
        signatures:
            # Here you can use the same signature definition but sometime you have some variations in your document, by setting a name you can inject the `document_type` as a query parameter to select the positions.
            document_type:
                -
                    page: 1 # default
                    x_position: 200 # top left corner in pixels
                    y_position: 400 # top left corner in pixels
            document_type_variation:
                -
                    page: 1 # default
                    x_position: 200 # top left corner in pixels
                    y_position: 400 # top left corner in pixels

        # league/flysystem-bundle storage usage
        storage: 'docusign.storage'

        # Storage declared by a previous signature definition
        storage: 'docusign.storage.my_remote_signature'

        # storage declared for this signature when not using league/flysystem-bundle
        storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

## Docusign Configurationn

### Types of signatures

- **Embedded Signature**: Allow to sign a document from a website or a mobile app by being redirected to docusign the sent back to the website or mobileApp
- **Remote Signature**: Send an Email to ask for a document signature.

### Format Restrictions:

| TYPE | EXTENSION |
|------:|:----------|
|DOCUMENT | .doc, .docm, .docx, .dot, .dotm, .dotx, .htm, .html, .msg, .pdf, .rtf, .txt, .wpd, .xps |
|IMAGE | .bmp, .gif, .jpg, .jpeg, .png, .tif, .tiff |
|PRESENTATION | .pot, .potx, .pps, .ppt, .pptm, .pptx |
|SPREADSHEET | .csv, .xls, .xlsm, .xlsx |

| RECOMMENDATION  |  SPEC |
|----------------:|:------|
|MAXIMUM FILE SIZE | 25 MB |
|RECOMMENDED FILE SIZE | 5 MB |
|MAXIMUM # PAGES PER DOCUMENT | 2,000 pages |

### Add an integration key on docusign

First go and authenticate yourself in `https://admindemo.docusign.com`

1. In DocuSign Admin, click API and Keys. ![click API and Keys.](/doc/assets/menu_api_and_keys.png)
1. Click `ADD APP` / `INTEGRATION KEY`. ![go to Add Integration key](/doc/assets/add_integration_key.png)
1. Add a name for your app, then click ADD. ![click API and Keys.](/doc/assets/integration_key.png)
1. Select an authentication type:
    - **JSON Web Token Grant (Service Integration)** - This is used for a service integration which integrates directly with a DocuSign account. It requires an RSA keypair.
        - Create an RSA keypair.
          To generate a new keypair, click `ADD RSA KEYPAIR`. ![click API and Keys.](/doc/assets/add_rsa_key.png)
          **Important:** Copy the RSA keys to a secure location immediately after you create them. Secret keys and RSA keys are displayed in plain text only once: when they are first created. After that, for security purposes, DocuSign only shows the last 4 digits of any secret keys or the RSA Keypair ID of any keypairs generated. Secrets should be stored securely within your application. They should never be shared or disclosed publicly.
          ![copy private key.](/doc/assets/copy_private_rsa_key.png)
1. Click `ADD URI` and enter a redirect `URI` for your application. When your application sends an authorization request to DocuSign, it includes the redirect `URI` in the request. The Account Server verifies that the URI in the request and the URI in the application registration match and sends a request to this URI to continue the process. Your application can have more than one redirect `URI`. ![redirect uri.](/doc/assets/add_redirect_uri.png)
1. Click `SAVE`. Your integration key is generated and added to the list of keys. ![user guid.](/doc/assets/user_guid.png)

## Basic usage

### Inside a twig template:

If you have only one signature without name

```twig
<a href="{{ path('docusign_sign_default', {'path': 'path/to/document.pdf'}) }}">sign</a>
```

If you have a named signature

```twig
<a href="{{ path('docusign_sign_SIGNATURENAME', {'path': 'path/to/document.pdf'}) }}">sign</a>
```

**Embedded signature**
- You'll get redirected to DocuSign website.
- DocuSign will redirect you to `docusign_callback` route.
- DocuSign will asynchronously send the result to `docusign_webhook` route.

**Remote signature**
- You'll get redirected to `docusign_callback` route.
- DocuSign will asynchronously send the result to `docusign_webhook` route.

### Using document variations signature positionning

You can define different variations for a document, so you can place your signatures at different places.
By default, if you've go only one type defined, you don't need to specify anything.
But from the moment you've got two, there is an ambiguity. You need to select which one to use.

```yml
# app/config/config.yml

docusign:
    # ...
    signatures:
        default:
            -
                page: 1 # default
                x_position: 200 # top left corner in pixels
                y_position: 400 # top left corner in pixels
        variation:
            -
                page: 2
                x_position: 500 # top left corner in pixels
                y_position: 800 # top left corner in pixels
    # ...
```

```twig
<a href="{{ path('docusign_sign_SIGNATURENAME', {'path': 'path/to/document.pdf', 'documentType': 'variation'}) }}">sign</a>
```

## Customization

### Configure a custom storage without league/flysystem-bundle

To access the document, we use the [`league/flysystem`](https://flysystem.thephpleague.com) library.

Create a class that implements the `League\Flysystem\FilesystemInterface` interface.
Now you can specify your class as adapter.


```yml
# app/config/config.yml

docusign:
    -
        # ...
        storage:
            docusign.storage:
                adapter: 'App\Your\Class'
```

### Use league/flysystem-bundle

#### Installation

```shell
$ composer require league/flysystem-bundle
```

#### Configure the storage
```yml
# config/packages/flysystem.yml
flysystem:
   storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

### Override configuration signature positions

Sometimes, you can not predict where to position the signatures in the document.
Or if you want an external service to define the positions you need to let the positionning to be set when called.
Set the configuration `signatures_overridable` option to true.

```yml
# app/config/config.yml

docusign:
    -
        # ...
        signatures_overridable: true
```

Then add a signature array to your query.

```php
$signatures = ['signatures'=>[['page'=>1,'x_position'=>200,'y_position'=>400]]]
$parameters = url_decode(http_build_query($a)); // signatures[0][page]=1&signatures[0][x_position]=200&signatures[0][y_position]=400`
```

It will override the signatures configured if any.

### Events

#### Sign events

When signing a document, DocuSign sends the user back to your application to the callback route.
But usually you need to send custom data to that route. To do that you can subscribe to the `PreSignEvent`.

Even more useful, when DocuSign redirect the user to the callback route, you might need to grab some data from the Request and modify the Response sent. You could even replace the Response object.
To do that you can subscribe to the `DocumentSignatureCompletedEvent`.


```php
// src/EventSubscriber/PreSignSubscriber.php
namespace App\EventSubscriber;

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

#### Webhook events

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

### Using an external route as callback

```yaml
external_docusign_callback:
    path: /internal/path/{eventual_parameter}
    defaults:
        _controller: FrameworkBundle:Redirect:urlRedirect
        path: "https://masterclass.les-tilleuls.coop"
        permanent: true
```

## Backward Compatibility promise

This library follows the same Backward Compatibility promise as the Symfony framework: [https://symfony.com/doc/current/contributing/code/bc.html](https://symfony.com/doc/current/contributing/code/bc.html)
