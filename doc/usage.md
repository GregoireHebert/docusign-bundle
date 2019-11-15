# Basic usage

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
