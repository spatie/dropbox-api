<?php

namespace Spatie\Dropbox\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<mixed>  $expectedParams
     */
    public function mockGuzzleRequest(string|StreamInterface|null $expectedResponse, string $expectedEndpoint, array $expectedParams): MockObject&GuzzleClient
    {
        $mockResponse = $this->createMock(ResponseInterface::class);

        if ($expectedResponse) {
            if (is_string($expectedResponse)) {
                $mockResponse->expects($this->once())
                    ->method('getBody')
                    ->willReturn($this->createStreamFromString($expectedResponse));
            } else {
                $mockResponse->expects($this->once())
                    ->method('getBody')
                    ->willReturn($expectedResponse);
            }
        }

        $mockGuzzle = $this->createMock(GuzzleClient::class);
        $mockGuzzle->expects($this->once())
            ->method('request')
            ->with('POST', $expectedEndpoint, $expectedParams)
            ->willReturn($mockResponse);

        return $mockGuzzle;
    }

    public function createStreamFromString(string $content): Stream
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);

        return new Stream($resource);
    }

    public function getClientMethod(string $name): \ReflectionMethod
    {
        $class = new \ReflectionClass(Client::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
