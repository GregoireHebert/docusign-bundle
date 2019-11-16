# Basic usage

## JWT consent agreement

When using the jwt grant authentication, you need to consent to DocuSign agreements.
It's a 1 time action you need to perform on each of your environments.

You need to consent for each specific signature configuration having a different integration key.
You need to call the URL `/docusign/consent/default` or `/docusign/consent/mySignature` if you've named your signature configuration `mySignature` from your browser.
In local it would be `http://localhost/docusign/consent/default`.

## Inside a twig template:

If you have only one signature without name:

```twig
<a href="{{ path('docusign_sign_default', {'path': 'path/to/document.pdf'}) }}">sign</a>
```

If you have a named signature:

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

Next: [Events](events.md)

[Go back](/README.md)
