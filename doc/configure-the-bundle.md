# Configure the bundle

Please ensure you correctly [configured docusign](#configure-docusign.md) first

[Configure JWT Grant](https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken) and set
your private key into `%kernel.project_dir%/var/jwt/docusign.pem`. This path is configurable as following:

## Create a default signature configuration

```yml
# config/packages/docusign.yml

docusign:
    mode: remote # Mode used to sign (remote, embedded or clickwrap)

    # Enable the Symfony Profiler
    enable_profiler: '%kernel.debug%'

    # Authentication credentials to log into docusign.
    auth_jwt:
        private_key: "YourPrivateKey"
        integration_key: "YourIntegrationKey"
        user_guid: "YourUserId"

    # Your DocuSign account id
    account_id: "YourAccountId"

    default_signer_name: "Grégoire Hébert" # Name of the person that will be notified and will sign the document if none is sent to the url.
    default_signer_email: "gregoire@les-tilleuls.coop" # Mail of the person that will be notified and will sign the document if none is sent to the url.

    api_uri: "https://www.docusign.net/restapi" # DocuSign api uri

    callback: "docusign_callback_default" # Your route where to redirect the user after signature

    # To sign you need to generate a route to call.
    # The route name will be formated `docusign.sign.{signaturename}` for this one it will be `docusign.sign.my_embedded_signature` and will have `my_embedded_signature` as attribute type
    # By defining this, you have a full control over the security applied to this route. see https://symfony.com/doc/current/security/access_control.html
    sign_path: '/my/embedded/sign/path'

    # Where to position the signature
    signatures:
        # this is an array of positions, you can have multiple signatures locations per pages
        -
            page: 1 # Default
            x_position: 200 # Top left corner in pixels
            y_position: 400 # Top left corner in pixels

    storage:
        adapter: 'local'
        options:
            directory: '%kernel.project_dir%/var/storage/default'
```

## Testing configuration

When set to true, it uses the demo url for DocuSign api_uri.

```yml
# config/packages/docusign.yml

docusign:
    demo: %kernel.debug%  # Default value: false
    # ...
```

## Document variations

If you use the same configuration for different document variations, you sometimes need to set different signatures positions according the doc.

```yml
# config/packages/docusign.yml

docusign:
    # ...
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
    # ...
```

Since there is an ambiguity, you need to specify now which documentType you want to use:

```twig
<a href="{{ path('docusign_sign_SIGNATURENAME', {'path': 'path/to/document.pdf', 'documentType': 'variation'}) }}">sign</a>
```

## Multiple signature configuration

If you have multiple accounts, or multiple types of signatures to handle, you need to configure each one of them.
You need to add a level of configuration which is a configuration name.

```yml
# config/packages/docusign.yml

docusign:
    default:
        mode: remote
        # ...

    second_signature:
        mode: embedded
        # ...

    # ...
```

### Reuse storage definition

If you already set a storage, you can reuse it on the next signature configuration.

```yml
# config/packages/docusign.yml

docusign:
    quotes:
        # ...
        storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage'

    contracts:
        # Storage declared by a previous signature definition
        storage: 'docusign.storage.quotes'
```

### Configure clickwrap

Clickwrap mode requires less configuration:

```yml
# config/packages/docusign.yml

docusign:
    terms:
        mode: clickwrap
        auth_clickwrap:
            api_account_id: "YourApiAccountId"
            clickwrap_id: "YourClickwrapId"
            user_guid: "YourUserId"
```

Next: [Basic usage](usage.md)

[Go back](/README.md)
