<?php

namespace Spatie\Dropbox;

class InMemoryTokenProvider implements TokenProvider
{
    public function __construct(protected string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
