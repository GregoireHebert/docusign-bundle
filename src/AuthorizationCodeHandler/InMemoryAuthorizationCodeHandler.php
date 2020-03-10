<?php

declare(strict_types=1);

namespace DocusignBundle\AuthorizationCodeHandler;

class InMemoryAuthorizationCodeHandler implements AuthorizationCodeHandlerInterface
{
    private $code;

    public function read(): string
    {
        return $this->code;
    }

    public function write(string $code): void
    {
        $this->code = $code;
    }
}
