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

use DocusignBundle\EnvelopeBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

final class PreSignEvent extends Event
{
    private $envelopeBuilder;
    private $request;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, Request $request)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->request = $request;
    }

    public function getEnvelopeBuilder(): EnvelopeBuilderInterface
    {
        return $this->envelopeBuilder;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
