<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
