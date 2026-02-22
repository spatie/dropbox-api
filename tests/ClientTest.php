<?php

/** @var \Spatie\Dropbox\Tests\TestCase $this */

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Spatie\Dropbox\Client;
use Spatie\Dropbox\Exceptions\BadRequest;
use Spatie\Dropbox\RefreshableTokenProvider;
use Spatie\Dropbox\UploadSessionCursor;

it('can be instantiated without auth', function () {
    $client = new Client;

    expect($client)->toBeInstanceOf(Client::class);
});

it('can be instantiated with token', function () {
    $client = new Client('test token');

    expect($client)->toBeInstanceOf(Client::class);
});

it('can be instantiated with key and secret', function () {
    $client = new Client(['test_key', 'test_secret']);

    expect($client)->toBeInstanceOf(Client::class);
});

it('can copy a file', function () {
    $expectedResponse = [
        '.tag' => 'file',
        'name' => 'Prime_Numbers.txt',
    ];

    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->copy('from/path/file.txt', 'to/path/file.txt'))->toBe($expectedResponse);
});

it('can search for files', function () {
    $expectedResponse = [
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

    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->search('test1.paper'))->toBe($expectedResponse);
});

it('can create a folder', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode(['name' => 'math']),
        'https://api.dropboxapi.com/2/files/create_folder',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
            'json' => [
                'path' => '/Homework/math',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->createFolder('Homework/math'))->toBe(['name' => 'math', '.tag' => 'folder']);
});

it('can delete a folder', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode(['name' => 'math']),
        'https://api.dropboxapi.com/2/files/delete',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
            'json' => [
                'path' => '/Homework/math',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->delete('Homework/math'))->toBe(['name' => 'math']);
});

it('can download a file', function () {
    $expectedResponse = $this->createMock(StreamInterface::class);
    $expectedResponse->expects($this->once())
        ->method('isReadable')
        ->willReturn(true);

    $mockGuzzle = $this->mockGuzzleRequest(
        $expectedResponse,
        'https://content.dropboxapi.com/2/files/download',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
                'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math/answers.txt']),
            ],
            'body' => '',
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->download('Homework/math/answers.txt'))->toBeResource();
});

it('can download a folder as zip', function () {
    $expectedResponse = $this->createMock(StreamInterface::class);
    $expectedResponse->expects($this->once())
        ->method('isReadable')
        ->willReturn(true);

    $mockGuzzle = $this->mockGuzzleRequest(
        $expectedResponse,
        'https://content.dropboxapi.com/2/files/download_zip',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
                'Dropbox-API-Arg' => json_encode(['path' => '/Homework/math']),
            ],
            'body' => '',
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->downloadZip('Homework/math'))->toBeResource();
});

it('can retrieve metadata', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode(['name' => 'math']),
        'https://api.dropboxapi.com/2/files/get_metadata',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
            'json' => [
                'path' => '/Homework/math',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->getMetadata('Homework/math'))->toBe(['name' => 'math']);
});

it('can get a temporary link', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->getTemporaryLink('Homework/math'))
        ->toBe('https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2');
});

it('can get a thumbnail', function () {
    $expectedResponse = $this->createMock(StreamInterface::class);

    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->getThumbnail('Homework/math/answers.jpg'))->toBeString();
});

it('can list a folder', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->listFolder('Homework/math', true))->toBe(['name' => 'math']);
});

it('can continue to list a folder', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode(['name' => 'math']),
        'https://api.dropboxapi.com/2/files/list_folder/continue',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
            'json' => [
                'cursor' => 'ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->listFolderContinue('ZtkX9_EHj3x7PMkVuFIhwKYXEpwpLwyxp9vMKomUhllil9q7eWiAu'))
        ->toBe(['name' => 'math']);
});

it('can move a file', function () {
    $expectedResponse = [
        '.tag' => 'file',
        'name' => 'Prime_Numbers.txt',
    ];

    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->move('/from/path/file.txt', '', false))->toBe($expectedResponse);
});

it('can upload a file', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->upload('Homework/math/answers.txt', 'testing text upload'))
        ->toBe(['name' => 'answers.txt', '.tag' => 'file']);
});

it('can start upload session', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);
    $uploadSessionCursor = $client->uploadSessionStart('this text have 23 bytes');

    expect($uploadSessionCursor)->toBeInstanceOf(UploadSessionCursor::class)
        ->and($uploadSessionCursor->session_id)->toBe('mockedUploadSessionId')
        ->and($uploadSessionCursor->offset)->toBe(23);
});

it('can append to upload session', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
                    ],
                ),
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => 'this text has 32 bytes',
        ],
    );

    $client = new Client('test_token', $mockGuzzle);
    $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);
    $uploadSessionCursor = $client->uploadSessionAppend('this text has 32 bytes', $oldUploadSessionCursor);

    expect($uploadSessionCursor)->toBeInstanceOf(UploadSessionCursor::class)
        ->and($uploadSessionCursor->session_id)->toBe('mockedUploadSessionId')
        ->and($uploadSessionCursor->offset)->toBe(32);
});

it('can finish an upload session', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);
    $oldUploadSessionCursor = new UploadSessionCursor('mockedUploadSessionId', 10);
    $response = $client->uploadSessionFinish(
        'this text has 32 bytes',
        $oldUploadSessionCursor,
        'Homework/math/answers.txt'
    );

    expect($response)->toBe([
        'name' => 'answers.txt',
        '.tag' => 'file',
    ]);
});

it('can get account info', function () {
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

    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode($expectedResponse),
        'https://api.dropboxapi.com/2/users/get_current_account',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->getAccountInfo())->toBe($expectedResponse);
});

it('can revoke token', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        null,
        'https://api.dropboxapi.com/2/auth/token/revoke',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);
    $client->revokeToken();
});

test('content endpoint request can throw exception', function () {
    $mockGuzzle = $this->createMock(GuzzleClient::class);
    $mockGuzzle->expects($this->once())
        ->method('request')
        ->willThrowException(new ClientException(
            'there was an error',
            $this->createMock(RequestInterface::class),
            $this->createMock(ResponseInterface::class),
        ));

    $client = new Client('test_token', $mockGuzzle);

    $client->contentEndpointRequest('testing/endpoint', []);
})->throws(ClientException::class);

test('contact endpoint request can be refreshed', function () {
    $tokenProvider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
        'getToken' => 'test_token',
    ]);

    $mockGuzzle = $this->createMock(GuzzleClient::class);

    $mockGuzzle->expects($this->exactly(2))
        ->method('request')
        ->willThrowException($e = new ClientException(
            'there was an error',
            $this->createMock(RequestInterface::class),
            $this->createMock(ResponseInterface::class),
        ));

    $tokenProvider->expects($this->once())
        ->method('refresh')
        ->with($e)
        ->willReturn(true);

    $client = new Client($tokenProvider, $mockGuzzle);

    $client->contentEndpointRequest('testing/endpoint', []);
})->throws(ClientException::class);

test('rpc endpoint request can throw exception with 400 status code', function () {
    $mockResponse = $this->createConfiguredMock(ResponseInterface::class, [
        'getStatusCode' => 400,
    ]);

    $mockGuzzle = $this->createMock(GuzzleClient::class);

    $mockGuzzle->expects($this->once())
        ->method('request')
        ->willThrowException(new ClientException(
            'there was an error',
            $this->createMock(RequestInterface::class),
            $mockResponse,
        ));

    $client = new Client('test_token', $mockGuzzle);

    $client->rpcEndpointRequest('testing/endpoint', []);
})->throws(BadRequest::class);

test('rpc endpoint request can throw exception with 409 status code', function () {
    $body = [
        'error' => [
            '.tag' => 'machine_readable_error_code',
        ],
        'error_summary' => 'Human readable error code',
    ];

    $mockResponse = $this->createConfiguredMock(ResponseInterface::class, [
        'getStatusCode' => 409,
        'getBody' => $this->createStreamFromString(json_encode($body)),
    ]);

    $mockGuzzle = $this->createMock(GuzzleClient::class);

    $mockGuzzle->expects($this->once())
        ->method('request')
        ->willThrowException(new ClientException(
            'there was an error',
            $this->createMock(RequestInterface::class),
            $mockResponse,
        ));

    $client = new Client('test_token', $mockGuzzle);

    $client->rpcEndpointRequest('testing/endpoint', []);
})->throws(BadRequest::class);

test('rpc endpoint request can be retried once', function () {
    $body = [
        'error' => [
            '.tag' => 'machine_readable_error_code',
        ],
        'error_summary' => 'Human readable error code',
    ];

    $tokenProvider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
        'getToken' => 'test_token',
    ]);

    $mockResponse = $this->createConfiguredMock(ResponseInterface::class, [
        'getStatusCode' => 409,
        'getBody' => $this->createStreamFromString(json_encode($body)),
    ]);

    $mockGuzzle = $this->createMock(GuzzleClient::class);

    $mockGuzzle->expects($this->exactly(2))
        ->method('request')
        ->willThrowException($e = new ClientException(
            'there was an error',
            $this->createMock(RequestInterface::class),
            $mockResponse,
        ));

    $tokenProvider->expects($this->once())
        ->method('refresh')
        ->with($e)
        ->willReturn(true);

    $client = new Client($tokenProvider, $mockGuzzle);

    $client->rpcEndpointRequest('testing/endpoint');
})->throws(BadRequest::class);

test('rpc endpoint request can be retried with success', function () {
    $errorBody = [
        'error' => [
            '.tag' => 'expired_access_token',
        ],
        'error_summary' => 'expired_access_token/',
    ];

    $successBody = [
        'access_token' => 'new_token',
    ];

    $tokenProvider = $this->createConfiguredMock(RefreshableTokenProvider::class, [
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

    $mockGuzzle = $this->createMock(GuzzleClient::class);

    $e = new ClientException(
        'there was an error',
        $this->createMock(RequestInterface::class),
        $errorResponse,
    );

    $callCount = 0;
    $mockGuzzle->expects($this->exactly(2))
        ->method('request')
        ->willReturnCallback(function () use (&$callCount, $e, $successResponse) {
            $callCount++;
            if ($callCount === 1) {
                throw $e;
            }

            return $successResponse;
        });

    $tokenProvider->expects($this->once())
        ->method('refresh')
        ->with($e)
        ->willReturn(true);

    $client = new Client($tokenProvider, $mockGuzzle);

    expect($client->rpcEndpointRequest('testing/endpoint'))->toBe($successBody);
});

it('can create a shared link', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
        json_encode(['name' => 'math']),
        'https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings',
        [
            'headers' => [
                'Authorization' => 'Bearer test_token',
            ],
            'json' => [
                'path' => '/Homework/math',
            ],
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->createSharedLinkWithSettings('Homework/math'))->toBe(['name' => 'math']);
});

it('can create a share link with custom settings', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    $settings = [
        'requested_visibility' => 'public',
    ];

    expect($client->createSharedLinkWithSettings('Homework/math', $settings))->toBe(['name' => 'math']);
});

it('can list shared links', function () {
    $mockGuzzle = $this->mockGuzzleRequest(
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
        ],
    );

    $client = new Client('test_token', $mockGuzzle);

    expect($client->listSharedLinks('Homework/math', true, 'mocked_cursor_id'))
        ->toBe(['url' => 'https://dl.dropboxusercontent.com/apitl/1/YXNkZmFzZGcyMzQyMzI0NjU2NDU2NDU2']);
});

it('can normalize paths', function () {
    $normalizeFunction = $this->getClientMethod('normalizePath');

    $client = new Client('test_token');

    // Default functionality of client to prepend slash for file paths requested
    expect($normalizeFunction->invokeArgs($client, ['/test/file/path']))->toBe('/test/file/path')
        ->and($normalizeFunction->invokeArgs($client, ['testurl']))->toBe('/testurl')
        ->and($normalizeFunction->invokeArgs($client, ['']))->toBe('')
        ->and($normalizeFunction->invokeArgs($client, ['file:1234567890']))->toBe('/file:1234567890')
    // If supplied with a direct id/ns/rev normalization should not prepend slash
        ->and($normalizeFunction->invokeArgs($client, ['id:1234567890']))->toBe('id:1234567890')
        ->and($normalizeFunction->invokeArgs($client, ['ns:1234567890']))->toBe('ns:1234567890')
        ->and($normalizeFunction->invokeArgs($client, ['rev:1234567890']))->toBe('rev:1234567890');
});

it('can get the access token', function () {
    $client = new Client('test_token');

    expect($client->getAccessToken())->toBe('test_token');
});

it('can set the access token', function () {
    $client = new Client('test_token');

    $client->setAccessToken('another_test_token');

    expect($client->getAccessToken())->toBe('another_test_token');
});

test('setting the namespace id will add it to the header', function () {
    $client = new Client('namespace_id');

    $expectedNamespaceId = '012345';
    $client->setNamespaceId($expectedNamespaceId);

    $getHeadersMethod = $this->getClientMethod('getHeaders');
    expect($getHeadersMethod->invoke($client))->toHaveKey('Dropbox-API-Path-Root');
});

it('can change the endpoint subdomain', function () {
    $client = new Client('test_token');

    $endpointFunction = $this->getClientMethod('getEndpointUrl');

    expect($endpointFunction->invokeArgs($client, ['api', 'files/delete']))
        ->toBe('https://api.dropboxapi.com/2/files/delete')
        ->and($endpointFunction->invokeArgs($client, ['api', 'content::files/get_thumbnail_batch']))
        ->toBe('https://content.dropboxapi.com/2/files/get_thumbnail_batch');
});
