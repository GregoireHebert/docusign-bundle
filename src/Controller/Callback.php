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

namespace DocusignBundle\Controller;

use DocusignBundle\DocusignBundle;
use DocusignBundle\Events\DocumentSignatureCompletedEvent;
use DocusignBundle\Translator\TranslatorAwareInterface;
use DocusignBundle\Translator\TranslatorAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class Callback implements TranslatorAwareInterface
{
    public const EVENT_COMPLETE = 'signing_complete';

    use TranslatorAwareTrait;

    public function __invoke(Request $request, EventDispatcherInterface $eventDispatcher): Response
    {
        if (self::EVENT_COMPLETE !== $status = $request->get('event')) {
            return new Response(
                $this->getTranslator()->trans(
                    'The document signature ended with an unexpected %status% status.',
                    ['%status%' => $status],
                    DocusignBundle::TRANSLATION_DOMAIN
                )
            );
        }

        $event = new DocumentSignatureCompletedEvent($request, new Response(
            $this->getTranslator()->trans(
                'Congratulations! The document have been successfully signed.',
                [],
                DocusignBundle::TRANSLATION_DOMAIN
            )
        ));

        $eventDispatcher->dispatch(DocumentSignatureCompletedEvent::class, $event);

        return $event->getResponse();
    }
}
