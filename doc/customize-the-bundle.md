# Customize the bundle

[Configure JWT Grant](https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken) and set
your private key into `%kernel.project_dir%/var/jwt/docusign.pem`. This path is configurable as following:

## Use the demo for testing

when set to true, it uses the sandbox url for docusign.

```yml
# config/packages/docusign.yml

docusign:
    demo: %kernel.debug%  # Default value: false
    # ...
```

## Configure a custom storage

To access the document, we use the [`league/flysystem`](https://flysystem.thephpleague.com) library.

Create a class that implements the `League\Flysystem\FilesystemInterface` interface.
Thanks to the autowiring and the autoconfiguration, you don't have anything else to do.
Now you need to specify your class as adapter.


```yml
# app/config/config.yml

docusign:
    # ...
    storage:
        docusign.storage:
            adapter: 'App\Your\Class'
```

## Override configuration signature positions

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

## Using an external route as callback

```yaml
external_docusign_callback:
    path: /internal/path/{eventual_parameter}
    defaults:
        _controller: FrameworkBundle:Redirect:urlRedirect
        path: "https://masterclass.les-tilleuls.coop"
        permanent: true
```

Next: [Use `league/flysystem-bundle`](use-flysystem-bundle.md)

[Go back](/README.md)
