<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

class WebHookEvent
{
    public const DOCUMENT_SIGNED = 'document.signed';
    public const DOCUMENT_PRE_SIGNED = 'document.pre.signed';
    public const DOCUMENT_SIGNATURE_COMPLETED = 'document.signature.completed';
}
