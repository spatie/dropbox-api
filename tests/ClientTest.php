<?php

namespace Spatie\Dropbox\Test;

use PHPUnit\Framework\TestCase;
use Spatie\Dropbox\Client;

class ClientTest extends TestCase
{
    /** @test */
    public function it_adds_request_headers()
    {
        $client = new Client('test_token');

        $authHeader = $client->client->getConfig('headers')['Authorization'];

        $this->assertEquals('Bearer test_token', $authHeader);
    }
}
