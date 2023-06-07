<?php

namespace Spatie\Dropbox\Test;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;
use Spatie\Dropbox\RefreshableTokenProvider;
use Spatie\Dropbox\UploadSessionCursor;

class ClientTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated_without_auth()
    {
        $client = new Client();

        $this->assertInstanceOf(Client::class, $client);
    }

    /** @test */
    public function it_can_be_instantiated_with_token()
    {
        $client = new Client('test_token');

        $this->assertInstanceOf(Client::class, $client);
    }

    /** @test */
    public function it_can_be_instantiated_with_key_and_secret()
    {
        $client = new Client(['test_key', 'test_secret']);

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
            'https://api.dropboxapi.com/2/files/copy_v2',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path' => '/to/path/file.txt',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->copy('from/path/file.txt', 'to/path/file.txt'));
    }

    /** @test */
    public function it_can_search_for_files()
    {
        $expectedResponse =
            [
                'matches' => [
                    0 => [
                        'metadata' => [
                            '.tag' => 'metadata',
                            'metadata' => [
                                '.tag' => 'file',
                                'name' => 'test1.paper',
                                'path_lower' => '/n/test1.paper',
                                'path_display' => '/n/test1.paper',
                                'id' => 'id:0XUXdYxPoJUAAAAAAAAACg',
                                'client_modified' => '2020-02-02T09:38:39Z',
                                'server_modified' => '2020-02-02T09:38:39Z',
                                'rev' => '59d94925cef7e2f00226e',
                                'size' => 0,
                                'is_downloadable' => false,
                                'export_info' => [
                                    'export_as' => 'html',
                                ],
                                'content_hash' => '54323eb0cd738a795362ea5a630863740fa38428f5a0ba7c45b29a0611234eec',
                            ],
                        ],
                    ],
                ],
                'has_more' => false,
            ];

        $mockGuzzle = $this->mock_guzzle_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/files/search_v2',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'query' => 'test1.paper',
                    'include_highlights' => false,
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->search('test1.paper'));
    }

    /** @test */
    public function it_can_create_a_folder()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/create_folder',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
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
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
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
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math/answers.txt']),
                ],
                'body' => '',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertTrue(is_resource($client->download('Homework/math/answers.txt')));
    }

    /** @test */
    public function it_can_download_a_folder_as_zip()
    {
        $expectedResponse = $this->getMockBuilder(StreamInterface::class)
            ->getMock();
        $expectedResponse->expects($this->once())
            ->method('isReadable')
            ->willReturn(true);

        $mockGuzzle = $this->mock_guzzle_request(
            $expectedResponse,
            'https://content.dropboxapi.com/2/files/download_zip',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math']),
                ],
                'body' => '',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertTrue(is_resource($client->downloadZip('Homework/math')));
    }

    /** @test */
    public function it_can_retrieve_metadata()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/files/get_metadata',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
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
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
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
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'path' => '/Homework/math/answers.jpg',
                            'format' => 'jpeg',
                            'size' => 'w64h64',
                        ]
                    ),
                ],
                'body' => '',
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
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'path' => '/Homework/math',
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
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
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
            'https://api.dropboxapi.com/2/files/move_v2',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'from_path' => '/from/path/file.txt',
                    'to_path' => '',
                    'autorename' => false,
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->move('/from/path/file.txt', '', false));
    }

    /** @test */
    public function it_can_upload_a_file()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'answers.txt']),
            'https://content.dropboxapi.com/2/files/upload',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode([
                        'path' => '/Homework/math/answers.txt',
                        'mode' => 'add',
                        'autorename' => false,
                    ]),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'testing text upload',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            ['.tag' => 'file', 'name' => 'answers.txt'],
            $client->upload('Homework/math/answers.txt', 'testing text upload')
        );
    }

    /** @test */
    public function it_can_start_upload_session()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['session_id' => 'mockedUploadSessionId']),
            'https://content.dropboxapi.com/2/files/upload_session/start',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'close' => false,
                        ]
                    ),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text have 23 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $uploadSessionCursor = $client->uploadSessionStart('this text have 23 bytes');

        $this->assertInstanceOf(UploadSessionCursor::class, $uploadSessionCursor);
        $this->assertEquals('mockedUploadSessionId', $uploadSessionCursor->session_id);
        $this->assertEquals(23, $uploadSessionCursor->offset);
    }

    /** @test */
    public function it_can_append_to_upload_session()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            null,
            'https://content.dropboxapi.com/2/files/upload_session/append_v2',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode(
                        [
                            'cursor' => [
                                'session_id' => 'mockedUploadSessionId',
                                'offset' => 10,
                            ],
                            'close' => false,
                        ]
                    ),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text has 32 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);

        $uploadSessionCursor = $client->uploadSessionAppend('this text has 32 bytes', $oldUploadSessionCursor);

        $this->assertInstanceOf(UploadSessionCursor::class, $uploadSessionCursor);
        $this->assertEquals('mockedUploadSessionId', $uploadSessionCursor->session_id);
        $this->assertEquals(32, $uploadSessionCursor->offset);
    }

    /** @test */
    public function it_can_upload_a_file_string_chunked()
    {
        $content = 'chunk0chunk1chunk2rest';
        $mockClient = $this->mock_chunked_upload_client($content, 6);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunked('Homework/math/answers.txt', $content, 'add', 6)
        );
    }

    /** @test */
    public function it_can_upload_a_file_resource_chunked()
    {
        $content = 'chunk0chunk1chunk2rest';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $mockClient = $this->mock_chunked_upload_client($content, 6);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunked('Homework/math/answers.txt', $resource, 'add', 6)
        );
    }

    /** @test */
    public function it_can_upload_a_tiny_file_chunked()
    {
        $content = 'smallerThenChunkSize';
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);
        rewind($resource);

        $mockClient = $this->mock_chunked_upload_client($content, 21);

        $this->assertEquals(
            ['name' => 'answers.txt'],
            $mockClient->uploadChunked('Homework/math/answers.txt', $resource, 'add', 21)
        );
    }

    /** @test */
    public function it_can_finish_an_upload_session()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode([
                'name' => 'answers.txt',
            ]),
            'https://content.dropboxapi.com/2/files/upload_session/finish',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                    'Dropbox-API-Arg' => json_encode([
                        'cursor' => [
                            'session_id' => 'mockedUploadSessionId',
                            'offset' => 10,
                        ],
                        'commit' => [
                            'path' => 'Homework/math/answers.txt',
                            'mode' => 'add',
                            'autorename' => false,
                            'mute' => false,
                        ],
                    ]),
                    'Content-Type' => 'application/octet-stream',
                ],
                'body' => 'this text has 32 bytes',
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);

        $response = $client->uploadSessionFinish(
            'this text has 32 bytes',
            $oldUploadSessionCursor,
            'Homework/math/answers.txt'
        );

        $this->assertEquals([
            '.tag' => 'file',
            'name' => 'answers.txt',
        ], $response);
    }

    /** @test */
    public function it_can_get_account_info()
    {
        $expectedResponse = [
            'account_id' => 'dbid:AAH4f99T0taONIb-OurWxbNQ6ywGRopQngc',
            'name' => [
                'given_name' => 'Franz',
                'surname' => 'Ferdinand',
                'familiar_name' => 'Franz',
                'display_name' => 'Franz Ferdinand (Personal)',
                'abbreviated_name' => 'FF',
            ],
            'email' => 'franz@gmail.com',
            'email_verified' => false,
            'disabled' => false,
            'locale' => 'en',
            'referral_link' => 'https://db.tt/ZITNuhtI',
            'is_paired' => false,
            'account_type' => [
                '.tag' => 'basic',
            ],
            'profile_photo_url' => 'https://dl-web.dropbox.com/account_photo/get/dbid%3AAAH4f99T0taONIb-OurWxbNQ6ywGRopQngc?vers=1453416673259&size=128x128',
            'country' => 'US',
        ];

        $mockGuzzle = $this->mock_guzzle_request(
            json_encode($expectedResponse),
            'https://api.dropboxapi.com/2/users/get_current_account',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals($expectedResponse, $client->getAccountInfo());
    }

    /** @test */
    public function it_can_revoke_token()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            null,
            'https://api.dropboxapi.com/2/auth/token/revoke',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $client->revokeToken();
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
    public function content_endpoint_request_can_be_refreshed()
    {
        $token_provider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
            'getToken' => 'test_token',
        ]);

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
            ->setMethods(['post'])
            ->getMock();

        $mockGuzzle->expects($this->exactly(2))
            ->method('post')
            ->willThrowException(
                $e = new ClientException(
                    'there was an error',
                    $this->getMockBuilder(RequestInterface::class)->getMock(),
                    $this->getMockBuilder(ResponseInterface::class)->getMock()
                )
            );

        $token_provider->expects($this->once())
            ->method('refresh')
            ->with($e)
            ->willReturn(true);

        $client = new Client($token_provider, $mockGuzzle);

        $this->expectException(ClientException::class);

        $client->contentEndpointRequest('testing/endpoint', []);
    }

    /** @test */
    public function rpc_endpoint_request_can_throw_exception_with_400_status_code()
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
    public function rpc_endpoint_request_can_throw_exception_with_409_status_code()
    {
        $body = [
            'error' => [
                '.tag' => 'machine_readable_error_code',
            ],
            'error_summary' => 'Human readable error code',
        ];

        $mockResponse = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();
        $mockResponse->expects($this->any())
            ->method('getStatusCode')
            ->willReturn(409);
        $mockResponse->expects($this->any())
            ->method('getBody')
            ->willReturn($this->createStreamFromString(json_encode($body)));

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
    public function rpc_endpoint_request_can_be_retried_once()
    {
        $body = [
            'error' => [
                '.tag' => 'machine_readable_error_code',
            ],
            'error_summary' => 'Human readable error code',
        ];

        $token_provider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
            'getToken' => 'test_token',
        ]);

        $mockResponse = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 409,
            'getBody' => $this->createStreamFromString(json_encode($body)),
        ]);

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
            ->setMethods(['post'])
            ->getMock();

        $mockGuzzle->expects($this->exactly(2))
            ->method('post')
            ->willThrowException(
                $e = new ClientException(
                    'there was an error',
                    $this->getMockBuilder(RequestInterface::class)->getMock(),
                    $mockResponse
                )
            );

        $token_provider->expects($this->once())
            ->method('refresh')
            ->with($e)
            ->willReturn(true);

        $this->expectException(BadRequest::class);

        $client = new Client($token_provider, $mockGuzzle);

        $client->rpcEndpointRequest('testing/endpoint');
    }

    /** @test */
    public function rpc_endpoint_request_can_be_retried_with_success()
    {
        $errorBody = [
            'error' => [
                '.tag' => 'expired_access_token',
            ],
            'error_summary' => 'expired_access_token/',
        ];

        $successBody = [
            'access_token' => 'new_token',
        ];

        $token_provider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
            'getToken' => 'test_token',
        ]);

        $errorResponse = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 409,
            'getBody' => $this->createStreamFromString(json_encode($errorBody)),
        ]);

        $successResponse = $this->createConfiguredMock(ResponseInterface::class, [
            'getStatusCode' => 200,
            'getBody' => $this->createStreamFromString(json_encode($successBody)),
        ]);

        $mockGuzzle = $this->getMockBuilder(GuzzleClient::class)
            ->setMethods(['post'])
            ->getMock();

        $e = new ClientException(
            'there was an error',
            $this->getMockBuilder(RequestInterface::class)->getMock(),
            $errorResponse
        );

        $mockGuzzle->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                $this->throwException($e),
                $successResponse
            );

        $token_provider->expects($this->once())
            ->method('refresh')
            ->with($e)
            ->willReturn(true);

        $client = new Client($token_provider, $mockGuzzle);

        $this->assertEquals($successBody, $client->rpcEndpointRequest('testing/endpoint'));
    }

    /** @test */
    public function it_can_create_a_shared_link()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'path' => '/Homework/math',
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(['name' => 'math'], $client->createSharedLinkWithSettings('Homework/math'));
    }

    /** @test */
    public function it_can_create_a_shared_link_with_custom_settings()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode(['name' => 'math']),
            'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'path' => '/Homework/math',
                    'settings' => [
                        'requested_visibility' => 'public',
                    ],
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $settings = [
            'requested_visibility' => 'public',
        ];

        $this->assertEquals(['name' => 'math'], $client->createSharedLinkWithSettings('Homework/math', $settings));
    }

    /** @test */
    public function it_can_list_shared_links()
    {
        $mockGuzzle = $this->mock_guzzle_request(
            json_encode([
                'name' => 'math',
                'links' => ['url' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2'],
            ]),
            'https://api.dropboxapi.com/2/sharing/list_shared_links',
            [
                'headers' => [
                    'Authorization' => 'Bearer test_token',
                ],
                'json' => [
                    'path' => '/Homework/math',
                    'cursor' => 'mocked_cursor_id',
                    'direct_only' => true,
                ],
            ]
        );

        $client = new Client('test_token', $mockGuzzle);

        $this->assertEquals(
            ['url' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2'],
            $client->listSharedLinks('Homework/math', true, 'mocked_cursor_id')
        );
    }

    /** @test */
    public function it_can_normalize_paths()
    {
        $normalizeFunction = self::getMethod('normalizePath');

        $client = new Client('test_token');

        //Default functionality of client to prepend slash for file paths requested
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['/test/file/path']), '/test/file/path');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['testurl']), '/testurl');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['']), '');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['file:1234567890']), '/file:1234567890');

        //If supplied with a direct id/ns/rev normalization should not prepend slash
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['id:1234567890']), 'id:1234567890');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['ns:1234567890']), 'ns:1234567890');
        $this->assertEquals($normalizeFunction->invokeArgs($client, ['rev:1234567890']), 'rev:1234567890');
    }

    /** @test */
    public function it_can_get_the_access_token()
    {
        $client = new Client('test_token');

        $this->assertEquals('test_token', $client->getAccessToken());
    }

    /** @test */
    public function it_can_set_the_access_token()
    {
        $client = new Client('test_token');

        $client->setAccessToken('another_test_token');

        $this->assertEquals('another_test_token', $client->getAccessToken());
    }

    /** @test */
    public function setting_the_namespace_id_will_add_it_to_the_header()
    {
        $client = new Client('namespace_id');

        $expectedNamespaceId = '012345';
        $client->setNamespaceId($expectedNamespaceId);

        $getHeadersMethod = static::getMethod('getHeaders');
        $this->assertArrayHasKey('Dropbox-API-Path-Root', $getHeadersMethod->invoke($client));
    }

    /** @test */
    public function it_can_change_the_endpoint_subdomain()
    {
        $client = new Client('test_token');

        $endpointFunction = static::getMethod('getEndpointUrl');

        $this->assertEquals($endpointFunction->invokeArgs($client, ['api', 'files/delete']), 'https://api.dropboxapi.com/2/files/delete');
        $this->assertEquals($endpointFunction->invokeArgs($client, ['api', 'content::files/get_thumbnail_batch']), 'https://content.dropboxapi.com/2/files/get_thumbnail_batch');
    }

    private function mock_guzzle_request($expectedResponse, $expectedEndpoint, $expectedParams)
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
            ->setMethods(['post'])
            ->getMock();
        $mockGuzzle->expects($this->once())
            ->method('post')
            ->with($expectedEndpoint, $expectedParams)
            ->willReturn($mockResponse);

        return $mockGuzzle;
    }

    private function mock_chunked_upload_client($content, $chunkSize)
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

        return $mockClient;
    }

    protected static function getMethod($name)
    {
        $class = new \ReflectionClass('Spatie\Dropbox\Client');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * @param  string  $content
     * @return Stream
     */
    private function createStreamFromString($content)
    {
        $resource = fopen('php://memory', 'r+');
        fwrite($resource, $content);

        return new Stream($resource);
    }
}
