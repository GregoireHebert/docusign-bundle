# Docusign

This Bundle is used to create electronic signature with DocuSign.
At the moment it only does handle implicit authentication with embedded signature.
Feel free to contribute :)

This bundle can be coupled with [FlySystem bundle](league/flysystem-bundle) to handle the files.
this bundle uses the same API/structure as the bundle offers. 

## Install

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

### Configure FlySystem

```yaml
flysystem:
    storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```


## Basic usage

*GET* `docusign` : `/docusign?path={document_path}`

You'll get redirected to docusign website.
Docusign will redirect you to `docusign_callback` : `/docusign/callback`
Docusign will also send the result to `docusign_webhook` : `/docusign/webhook`

If you need to override one, it's the callback one. :)
