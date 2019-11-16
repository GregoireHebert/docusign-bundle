# Bundle Installation

```shell
$ composer require gheb/docusign-bundle
```

## register the bundle

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

Next: [Configure DocuSign](configure-docusign.md)


[Go back](/README.md)
