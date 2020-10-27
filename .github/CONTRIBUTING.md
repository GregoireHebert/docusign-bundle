# Contributing to Docusign bundle

First of all, thank you for contributing, you're awesome!

To have your code integrated in the Docusign bundle, there are some rules to follow, but don't panic, it's easy!

## Reporting Bugs

If you happen to find a bug, we kindly request you to report it. However, before submitting it, please:

* Check the documentation.

Then, if it appears that it's a real bug, you may report it using GitHub by following these 3 points:

* Check if the bug is not already reported!
* A clear title to resume the issue
* A description of the workflow needed to reproduce the bug

> _NOTE:_ Don't hesitate giving as much information as you can (OS, PHP version extensions...)

## Pull Requests

### Writing a Pull Request

First of all, you must decide on what branch your changes will be based depending of the nature of the change.
If this is a bug, you should target the branch where the bug appeared.
If this is a new feature, you should target master.

### Matching Coding Standards

The Docusign bundle follows [Symfony coding standards](https://symfony.com/doc/current/contributing/code/standards.html).
But don't worry, you can fix CS issues automatically using the [PHP CS Fixer](https://cs.sensiolabs.org/) tool:

```shell
$ vendor/bin/php-cs-fixer fix
```

And then, add the fixed file to your commit before pushing.
Be sure to add only **your modified files**. If any other file is fixed by cs tools, just revert it before committing.

### Sending a Pull Request

When you send a PR, just make sure that:

* You add valid test cases.
* Tests are green.
* You've added documentation when necessary
* You make the PR on the same branch you based your changes on. If you see commits
that you did not make in your PR, you're doing it wrong.
* Also don't forget to add a comment when you update a PR with a ping to maintainer (@gregoirehebert or @vincentchalamon)
* Squash your commits into one commit (see the next chapter).

All Pull Requests must include [this header](.github/PULL_REQUEST_TEMPLATE.md).

### Static code analysis

```shell
vendor/bin/phpstan analyze
```

### PHPUnit and Coverage Generation

To launch unit tests:

```shell
vendor/bin/phpunit
```

If you want coverage, you will need the `phpdbg` package and run:

```shell
phpdbg -qrr vendor/bin/phpunit --coverage-html coverage
```

Sometimes there might be an error with too many open files when generating coverage. To fix this, you can increase the `ulimit`, for example:

```shell
ulimit -n 4000
```

Coverage will be available in `coverage/index.html`.

## Squash your Commits

If you have 3 commits, start with:

```shell
git rebase -i HEAD~3
```

An editor will be opened with your 3 commits, all prefixed by `pick`.

Replace all `pick` prefixes by `fixup` (or `f`) **except the first commit** of the list.

Save and quit the editor.

After that, all your commits will be squashed into the first one and the commit message will be the first one.

If you would like to rename your commit message, type:

```shell
git commit --amend
```

Now force push to update your PR:

```shell
git push --force-with-lease
```

# Run the project locally

Create the `features/.env.local.php` file as following:

```php
<?php

return [
    'DOCUSIGN_INTEGRATION_KEY' => 'your-personal-integration-key',
    'DOCUSIGN_USER_GUID' => 'your-personal-user-guid',
    'DOCUSIGN_ACCOUNT_ID' => your-personal-account-id,
    'DOCUSIGN_CLICKWRAP_ID' => 'your-clickwrap-id',
    'DOCUSIGN_API_ACCOUNT_ID' => 'your-api-account-id',
    'DOCUSIGN_SECRET' => 'your-secret',
    'DOCUSIGN_DEFAULT_SIGNER_NAME' => 'your-name',
    'DOCUSIGN_DEFAULT_SIGNER_EMAIL' => 'your-email',
    'DOCUSIGN_EMAIL' => 'your-docusign-email',
    'DOCUSIGN_PASSWORD' => 'your-docusign-password',
    'APP_ENV' => 'test',
    'APP_DEBUG' => true,
];
```

Then, you must define the following urls in the `Redirect URIs` section on DocuSign Admin:

https://127.0.0.1:8000
https://127.0.0.1:8000
https://127.0.0.1:8000/docusign/authorization_code/embedded_auth_code
https://127.0.0.1:8000/docusign/authorization_code/remote_auth_code

Finally, [generate an RSA key pair](https://developers.docusign.com/esign-rest-api/guides/authentication/oauth2-jsonwebtoken)
on DocuSign and store the private key on `features/var/jwt/docusign.pem`.

## Starting the demo project

You must install the [Symfony binary](https://symfony.com/download), then start the server:

```shell
symfony serve --document-root=features/public
```

Then go to http://127.0.0.1:8000.

## Debugging

The [WebProfilerBundle](https://symfony.com/web-profiler-bundle) is available at http://127.0.0.1:8000/_profiler/.

## List of documents

To access the list of documents, you'll need to login as `admin:4dm1n` on http://127.0.0.1:8000/.

## Running tests

You must add the following urls in the `Redirect URIs` section on DocuSign Admin:

http://127.0.0.1:9080
http://127.0.0.1:9080
http://127.0.0.1:9080/docusign/authorization_code/embedded_auth_code
http://127.0.0.1:9080/docusign/authorization_code/remote_auth_code

Then, run the following command to execute e2e tests:

```shell
vendor/bin/phpunit --testdox
```

# License and Copyright Attribution

When you open a Pull Request to the Docusign bundle, you agree to license your code under the [MIT license](LICENSE)
and to transfer the copyright on the submitted code to Grégoire Hébert.

Be sure to you have the right to do that (if you are a professional, ask your company)!

If you include code from another project, please mention it in the Pull Request description and credit the original author.
