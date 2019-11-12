<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocusignBundle\EnvelopeBuilderInterface;

interface EnvelopeCreatorInterface
{
    public function createEnvelope(EnvelopeBuilderInterface $envelopeBuilder): string;
}
