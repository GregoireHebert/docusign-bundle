# Docusign

This Bundle is used to create electronic signature with DocuSign.
At the moment it only does handle implicit authentication with embedded signature.
Feel free to contribute :)

This bundle can be coupled with [FlySystem bundle](league/flysystem-bundle) to handle the files.
this bundle uses the same API/structure as the bundle offers. 

## install

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

### Add routing

```yml
# app/config/routing.yml

docusign:
    resource: '@DocusignBundle/Resources/config/routing.yml'
```

### Add configuration

Check the [official documentation](https://github.com/docusign/qs-php) to get your access token and account Id.

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

```

## Basic usage

```php
use DocusignBundle\EnvelopeBuilder;

$signatureBuilder = new EnvelopeBuilder($accessToken, $accountId, $defaultSignerName, $defaultSignerEmail, $apiURI);

$urlToRedirect = $enveloperBuilder
        ->setFile('myFile')
        ->addSignatureZone(1, 200, 200)
        ->createEnvelope();
```

## Symfony Usage

Import the `DocusignBundle\ESignatureBuilder` service thanks to the dependency injection.
Grab the url and return a `new RedirectResponse($urlToRedirect, 307)`.

## Using FlySystem Bundle

```yaml

flysystem:
    storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```
