<?php

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\ApiException;
use DocusignBundle\EnvelopeBuilderInterface;

interface EnvelopeBuilderCallableInterface
{
    /**
     * @throws ApiException
     *
     * @return void|string when the function return a string, it will leave the chain
     */
    public function __invoke(EnvelopeBuilderInterface $envelopeBuilder, array $context = []);
}
