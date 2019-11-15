# Events

## Sign events

When signing a document, DocuSign sends the user back to your application to the callback route.
But usually you need to send custom data to that route. To do that you can subscribe to the `PreSignEvent`.

Even more useful, when DocuSign redirect the user to the callback route, you might need to grab some data from the Request and modify the Response sent. You could even replace the Response object.
To do that you can subscribe to the `DocumentSignatureCompletedEvent`.


```php
// src/EventSubscriber/PreSignSubscriber.php
namespace App\EventSubscriber;

use DocusignBundle\EnvelopeBuilder;
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

## Webhook events

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

Next: [Customize the bundle](customize-the-bundle.md)

[Go back](README.md)
