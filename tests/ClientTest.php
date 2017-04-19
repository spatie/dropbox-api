<?php

namespace Spatie\Dropbox\Test;

use Spatie\Dropbox\Client;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $client = new Client('test_token');

        $this->assertInstanceOf(Client::class, $client);
    }
}
