<?php

namespace Spatie\Dropbox\Test;

use Spatie\Dropbox\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
use Spatie\Dropbox\Exceptions\BadRequest;

class ClientTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated()
    {
        $client = new Client('test_token');

        $this->assertInstanceOf(Client::class, $client);
    }

    /** @test */
    public function it_can_copy_a_file()
    {
        $expectedResponse = [
            '.tag' => 'file',
            'name' => 'Prime_Numbers.txt',
        ];

        $mockGuzzle = $this->mock_guzzle_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/files/copy',
            [
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path'   => '/to/path/file.txt',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->copy('from/path/file.txt', 'to/path/file.txt'));
    }

    /** @test */
    public function it_can_create_a_folder()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/create_folder',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['.tag' => 'folder', 'name' => 'math'], $client->createFolder('Homework/math'));
    }

    /** @test */
    public function it_can_delete_a_folder()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/delete',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->delete('Homework/math'));
    }

    /** @test */
    public function it_can_download_a_file()
    {
        $expectedResponse = $this->getMockBuilder(StreamInterface::class)
                                 ->getMock();
        $expectedResponse->expects($this->once())
                         ->method('isReadable')
                         ->willReturn(true);

        $mockGuzzle = $this->mock_guzzle_request(
            $expectedResponse,
            'https://content.dropboxapi.com/2/files/download',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math/answers.txt']),
                ],
                'body'    => '',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertTrue(is_resource($client->download('Homework/math/answers.txt')));
    }

    /** @test */
    public function it_can_retrieve_metadata()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/get_metadata',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->getMetadata('Homework/math'));
    }

    /** @test */
    public function it_can_get_a_temporary_link()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode([
                'name' => 'math',
                'link' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2',
            ]),
            'https://api.dropboxapi.com/2/files/get_temporary_link',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2',
            $client->getTemporaryLink('Homework/math')
        );
    }

    /** @test */
    public function it_can_get_a_thumbnail()
    {
        $expectedResponse = $this->getMockBuilder(StreamInterface::class)
                                 ->getMock();

        $mockGuzzle = $this->mock_guzzle_request(
            $expectedResponse,
            'https://content.dropboxapi.com/2/files/get_thumbnail',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'path'   => '/Homework/math/answers.jpg',
                            'format' => 'jpeg',
                            'size'   => 'w64h64',
                        ]
                    ),
                ],
                'body'    => '',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertTrue(is_string($client->getThumbnail('Homework/math/answers.jpg')));
    }

    /** @test */
    public function it_can_list_a_folder()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/list_folder',
            [
                'json' => [
                    'path'      => '/Homework/math',
                    'recursive' => true,
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->listFolder('Homework/math', true));
    }

    /** @test */
    public function it_can_continue_to_list_a_folder()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/list_folder/continue',
            [
                'json' => [
                    'cursor' => 'ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            ['name' => 'math'],
            $client->listFolderContinue('ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu')
        );
    }

    /** @test */
    public function it_can_move_a_file()
    {
        $expectedResponse = [
            '.tag' => 'file',
            'name' => 'Prime_Numbers.txt',
        ];

        $mockGuzzle = $this->mock_guzzle_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/files/move',
            [
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path'   => '',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->move('/from/path/file.txt', ''));
    }

    /** @test */
    public function it_can_upload_a_file()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'answers.txt']),
            'https://content.dropboxapi.com/2/files/upload',
            [
                'headers' => [
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'path' => '/Homework/math/answers.txt',
                            'mode' => 'add',
                        ]
                    ),
                    'Content-Type'    => 'application/octet-stream',
                ],
                'body'    => 'testing text upload',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            ['.tag' => 'file', 'name' => 'answers.txt'],
            $client->upload('Homework/math/answers.txt', 'testing text upload')
        );
    }

    /** @test */
    public function content_endpoint_request_can_throw_exception()
    {
        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
                           ->setMethods(['post'])
                           ->getMock();
        $mockGuzzle->expects($this->once())
                   ->method('post')
                   ->willThrowException(
                       new ClientException(
                           'there was an error',
                           $this->getMockBuilder(RequestInterface::class)->getMock(),
                           $this->getMockBuilder(ResponseInterface::class)->getMock()
                       )
                   );

        $client = new Client('test_token', $mockGuzzle);

        $this->expectException(ClientException::class);

        $client->contentEndpointRequest('testing/endpoint', []);
    }

    /** @test */
    public function rpc_endpoint_request_can_throw_exception()
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
                             ->getMock();
        $mockResponse->expects($this->any())
                     ->method('getStatusCode')
                     ->willReturn(400);

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
                           ->setMethods(['post'])
                           ->getMock();

        $mockGuzzle->expects($this->once())
                   ->method('post')
                   ->willThrowException(
                       new ClientException(
                           'there was an error',
                           $this->getMockBuilder(RequestInterface::class)->getMock(),
                           $mockResponse
                       )
                   );

        $client = new Client('test_token', $mockGuzzle);

        $this->expectException(BadRequest::class);

        $client->rpcEndpointRequest('testing/endpoint', []);
    }

    /** @test */
    public function it_can_create_a_shared_link()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings',
            [
                'json' => [
                    'path'      => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->createSharedLinkWithSettings('Homework/math'));
    }

    /** @test */
    function it_can_list_shared_links()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode([
                'name' => 'math',
                'links' => ['url' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2'],
            ]),
            'https://api.dropboxapi.com/2/sharing/list_shared_links',
            [
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            ['url' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2'],
            $client->listSharedLinks('Homework/math')
        );
    }

    private function mock_guzzle_request($expectedResponse, $expectedEndpoint, $expectedParams)
    {
        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
                             ->getMock();
        $mockResponse->expects($this->once())
                     ->method('getBody')
                     ->willReturn($expectedResponse);

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
                           ->setMethods(['post'])
                           ->getMock();
        $mockGuzzle->expects($this->once())
                   ->method('post')
                   ->with($expectedEndpoint, $expectedParams)
                   ->willReturn($mockResponse);

        return $mockGuzzle;
    }
}
