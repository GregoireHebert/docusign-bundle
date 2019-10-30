<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

use DocusignBundle\EnvelopeBuilder;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

final class PreSignEvent extends Event
{
    private $envelopeBuilder;
    private $request;

    public function __construct(EnvelopeBuilder $envelopeBuilder, Request $request)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->request = $request;
    }

    public function getEnvelopeBuilder(): EnvelopeBuilder
    {
        return $this->envelopeBuilder;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
