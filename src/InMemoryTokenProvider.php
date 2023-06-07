<?php

namespace Spatie\Dropbox;

class InMemoryTokenProvider implements TokenProvider
{
    public function __construct(private readonly string $token)
    {
    }

    public function getToken(): string
    {
        return $this->token;
    }
}
