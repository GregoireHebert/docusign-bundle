<?php

declare(strict_types=1);

namespace DocusignBundle\AuthorizationCodeHandler;

interface AuthorizationCodeHandlerInterface
{
    public function read(): string;
    public function write(string $code);
}
