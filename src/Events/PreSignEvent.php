<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

use DocusignBundle\EnvelopeBuilder;
use Symfony\Contracts\EventDispatcher\Event;

final class PreSignEvent extends Event
{
    private $envelopeBuilder;

    public function __construct(EnvelopeBuilder $envelopeBuilder)
    {
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function getEnvelopeBuilder(): EnvelopeBuilder
    {
        return $this->envelopeBuilder;
    }
}
