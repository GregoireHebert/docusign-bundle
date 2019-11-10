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

namespace DocusignBundle\E2e\TestBundle\EventSubscriber;

use DocusignBundle\Events\CompletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DocusignEventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            CompletedEvent::class => 'onDocumentSigned',
        ];
    }

    public function onDocumentSigned(CompletedEvent $event): void
    {
        // todo Save signed document ($event->getData()) on storage as <document-name>-signed.pdf
    }
}
