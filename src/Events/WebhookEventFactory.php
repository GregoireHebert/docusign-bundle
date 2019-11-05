<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

use DocusignBundle\Exception\InvalidStatusException;

final class WebhookEventFactory
{
    public static function create(string $status, ...$args): WebhookEvent
    {
        switch ($status) {
            default:
                throw new InvalidStatusException("Invalid status $status.");
            case 'Sent':
                return new SentEvent(...$args);
            case 'Delivered':
                return new DeliveredEvent(...$args);
            case 'Completed':
                return new CompletedEvent(...$args);
            case 'Declined':
                return new DeclinedEvent(...$args);
            case 'AuthenticationFailed':
                return new AuthenticationFailedEvent(...$args);
            case 'AutoResponded':
                return new AutoRespondedEvent(...$args);
        }
    }
}
