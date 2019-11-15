# Use league/flysystem-bundle

## Installation

```shell
$ composer require league/flysystem-bundle
```

## Configure the storage

```yml
# config/packages/flysystem.yml
flysystem:
   storages:
        docusign.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/storage/default'
```

## Configure the signature

You need to define as storage the name of the flysystem storage.

```yml
# config/packages/docusign.yml

docusign:
    # ...
    storage: 'docusign.storage'
```

[Go back](/README.md)
