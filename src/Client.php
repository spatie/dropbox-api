<?php

namespace Spatie\Dropbox;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\StreamWrapper;
use Psr\Http\Message\ResponseInterface;
use Spatie\Dropbox\Exceptions\BadRequest;

class Client
{
    const THUMBNAIL_FORMAT_JPEG = 'jpeg';
    const THUMBNAIL_FORMAT_PNG = 'png';

    const THUMBNAIL_SIZE_XS = 'w32h32';
    const THUMBNAIL_SIZE_S = 'w64h64';
    const THUMBNAIL_SIZE_M = 'w128h128';
    const THUMBNAIL_SIZE_L = 'w640h480';
    const THUMBNAIL_SIZE_XL = 'w1024h768';

    const HTTP_BAD_REQUEST = 400
    const HTTP_CONFLICT = 409

    protected $accessToken;

    protected $client;

    public function __construct(string $accessToken)
    {
        $this->accessToken = $accessToken;

        $this->client = new GuzzleClient([
            'headers' => [
                'Authorization' => "Bearer {$this->accessToken}",
            ],
        ]);
    }

    /**
     * Copy a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be copied.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-copy
     */
    public function copy(string $path, string $newPath): array
    {
        $parameters = [
            'from_path' => $this->normalizePath($path),
            'to_path' => $this->normalizePath($newPath),
        ];

        return $this->rpcEndpointRequest('files/copy', $parameters);
    }

    /**
     * Create a folder at a given path.Create a folder at a given path.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-create_folder
     */
    public function createFolder(string $path): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        $object = $this->rpcEndpointRequest('files/create_folder', $parameters);

        $object['.tag'] = 'folder';

        return $object;
    }

    /**
     * Delete the file or folder at a given path.
     *
     * If the path is a folder, all its contents will be deleted too.
     * A successful response indicates that the file or folder was deleted.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-delete
     */
    public function delete(string $path): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        return $this->rpcEndpointRequest('files/delete', $parameters);
    }

    /**
     * Download a file from a user's Dropbox.
     *
     * @param string $path
     *
     * @return resource
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-download
     */
    public function download(string $path)
    {
        $arguments = [
            'path' => $this->normalizePath($path),
        ];

        $response = $this->contentEndpointRequest('files/download', $arguments);

        return StreamWrapper::getResource($response->getBody());
    }

    /**
     * Returns the metadata for a file or folder.
     *
     * Note: Metadata for the root folder is unsupported.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
     */
    public function getMetadata(string $path): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        return $this->rpcEndpointRequest('files/get_metadata', $parameters);
    }

    /**
     * Get a temporary link to stream content of a file.
     *
     * This link will expire in four hours and afterwards you will get 410 Gone.
     * Content-Type of the link is determined automatically by the file's mime type.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_temporary_link
     */
    public function getTemporaryLink(string $path): string
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        $body = $this->rpcEndpointRequest('files/get_temporary_link', $parameters);

        return $body['link'];
    }

    /**
     * Get a thumbnail for an image.
     *
     * This method currently supports files with the following file extensions:jpg, jpeg,
     * png, tiff, tif, gif and bmp.
     * Photos that are larger than 20MB in size won't be converted to a thumbnail.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_thumbnail
     */
    public function getThumbnail(string $path, string $format = 'jpeg', string $size = 'w64h64'): string
    {
        $arguments = [
            'path' => $this->normalizePath($path),
            'format' => $format,
            'size' => $size
        ];

        $response = $this->contentEndpointRequest('files/get_thumbnail', $arguments);

        return (string)$response->getBody();
    }

    /**
     * Starts returning the contents of a folder.
     *
     * If the result's ListFolderResult.has_more field is true, call
     * list_folder/continue with the returned ListFolderResult.cursor to retrieve more entries.
     *
     * Note: auth.RateLimitError may be returned if multiple list_folder or list_folder/continue calls
     * with same parameters are made simultaneously by same API app for same user. If your app implements
     * retry logic, please hold off the retry until the previous request finishes.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
     */
    public function listFolder(string $path = '', bool $recursive = false): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
            'recursive' => $recursive,
        ];

        return $this->rpcEndpointRequest('files/list_folder', $parameters);
    }

    /**
     * Move a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be moved.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-move
     */
    public function move(string $path, string $newPath): array
    {
        $parameters = [
            'from_path' => $this->normalizePath($path),
            'to_path' => $this->normalizePath($newPath),
        ];

        return $this->rpcEndpointRequest('files/move', $parameters);
    }

    /**
     * Create a new file with the contents provided in the request.
     *
     * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload
     *
     * @param string $path
     * @param string $mode
     * @param string|resource $contents
     *
     * @return array
     */
    public function upload(string $path, string $mode, $contents): array
    {
        $arguments = [
            'path' => $this->normalizePath($path),
            'mode' => $mode,
        ];

        $response = $this->contentEndpointRequest('files/upload', $arguments, $contents);

        $metadata = json_decode($response->getBody(), true);

        $metadata['.tag'] = 'file';

        return $metadata;
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        if ($path === '') {
            return '';
        }

        return '/' . $path;
    }

    /**
     * @param string $endpoint
     * @param array $arguments
     * @param string|resource $body
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Spatie\Dropbox\Exceptions\BadRequest
     */
    protected function contentEndpointRequest(string $endpoint, array $arguments, $body = ''): ResponseInterface
    {
        $headers['Dropbox-API-Arg'] = json_encode($arguments);

        if ($body !== '') {
            $headers['Content-Type'] = 'application/octet-stream';
        }

        try {
            $response = $this->client->post("https://content.dropboxapi.com/2/{$endpoint}", [
                'headers' => $headers,
                'body' => $body,
            ]);

        } catch (ClientException $exception) {
            if (in_array($exception->getResponse()->getStatusCode(), [
                static::HTTP_BAD_REQUEST,
                static::HTTP_CONFLICT,
            ])) {
                throw new BadRequest($exception->getResponse());
            }

            throw $exception;
        }

        return $response;
    }

    protected function rpcEndpointRequest(string $endpoint, array $parameters): array
    {
        try {
            $response = $this->client->post("https://api.dropboxapi.com/2/{$endpoint}", [
                'json' => $parameters
            ]);
        } catch (ClientException $exception) {
            if (in_array($exception->getResponse()->getStatusCode(), [
                static::HTTP_BAD_REQUEST,
                static::HTTP_CONFLICT,
            ])) {
                throw new BadRequest($exception->getResponse());
            }

            throw $exception;
        }

        return json_decode($response->getBody(), true);
    }
}
