<?php

namespace Spatie\Dropbox\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\UploadSessionCursor;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<mixed>  $expectedParams
     */
    public function mockGuzzleRequest(string|StreamInterface|null $expectedResponse, string $expectedEndpoint, array $expectedParams): MockObject&GuzzleClient
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();

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

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
            ->onlyMethods(['request'])
            ->getMock();
        $mockGuzzle->expects($this->once())
            ->method('request')
            ->with('POST', $expectedEndpoint, $expectedParams)
            ->willReturn($mockResponse);

        return $mockGuzzle;
    }

    public function mockChunkedUploadClient(string $content, int $chunkSize): MockObject&Client
    {
        $chunks = str_split($content, $chunkSize);

        $mockClient = $this->getMockBuilder(Client::class)
            ->setConstructorArgs(['test_token'])
            ->setMethodsExcept(['uploadChunked', 'upload'])
            ->getMock();

        $mockClient->expects($this->once())
            ->method('uploadSessionStart')
            ->with(array_shift($chunks))
            ->willReturn(new UploadSessionCursor('mockedSessionId', $chunkSize));

        $mockClient->expects($this->once())
            ->method('uploadSessionFinish')
            ->with('', $this->anything(), 'Homework/math/answers.txt', 'add')
            ->willReturn(['name' => 'answers.txt']);

        $remainingChunks = count($chunks);
        $offset = $chunkSize;

        if ($remainingChunks) {
            $withs = [];
            $returns = [];

            foreach ($chunks as $chunk) {
                $offset += $chunkSize;
                $withs[] = [$chunk, $this->anything()];
                $returns[] = new UploadSessionCursor('mockedSessionId', $offset);
            }

            $mockClient->expects($this->exactly($remainingChunks))
                ->method('uploadSessionAppend')
                ->withConsecutive(...$withs)
                ->willReturn(...$returns);
        }

        \assert($mockClient instanceof Client);

        return $mockClient;
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
