<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

use Symfony\Component\EventDispatcher\Event;

class DocumentSignatureCompleted extends Event
{
    /**
     * @var string
     */
    private $envelopeId;

    public function __construct($envelopeId)
    {
        $this->envelopeId = $envelopeId;
    }

    /**
     * @return string the envelope Id
     */
    public function getEnvelopeId(): string
    {
        return $this->envelopeId;
    }
}
