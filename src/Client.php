<?php

namespace Spatie\Dropbox;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\StreamWrapper;

class Client
{
    const THUMBNAIL_FORMAT_JPEG = 'jpeg';
    const THUMBNAIL_FORMAT_PNG = 'png';

    const THUMBNAIL_SIZE_XS = 'w32h32';
    const THUMBNAIL_SIZE_S = 'w64h64';
    const THUMBNAIL_SIZE_M = 'w128h128';
    const THUMBNAIL_SIZE_L = 'w640h480';
    const THUMBNAIL_SIZE_XL = 'w1024h768';

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

        return $this->requestRPC('files/copy', $parameters);
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

        $object = $this->requestRPC('files/create_folder', $parameters);

        $object['.tag'] = 'folder';

        return $object;
    }

    /**
     * Delete the file or folder at a given path.
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

        return $this->requestRPC('files/delete', $parameters);
    }

    /**
     * Download a file from a user's Dropbox.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-download
     */
    public function download(string $path): resource
    {
        $dropboxApiArguments = [
            'path' => $this->normalizePath($path),
        ];

        $response = $this->client->post('https://content.dropboxapi.com/2/files/download', [
            'headers' => [
                'Dropbox-API-Arg' => json_encode($dropboxApiArguments),
            ],
        ]);

        return StreamWrapper::getResource($response->getBody());
    }

    /**
     * Returns the metadata for a file or folder.
     * Note: Metadata for the root folder is unsupported.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_metadata
     */
    public function getMetadata(string $path): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        return $this->requestRPC('files/get_metadata', $parameters);
    }

    /**
     * Get a temporary link to stream content of a file. This link will expire in four hours and afterwards you will get 410 Gone.
     * Content-Type of the link is determined automatically by the file's mime type.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_temporary_link
     */
    public function getTemporaryLink(string $path): string
    {
        $parameters = [
            'path' => $this->normalizePath($path),
        ];

        $body = $this->requestRPC('files/get_temporary_link', $parameters);

        return $body['link'];
    }

    /**
     * Get a thumbnail for an image.
     * This method currently supports files with the following file extensions: jpg, jpeg, png, tiff, tif, gif and bmp.
     * Photos that are larger than 20MB in size won't be converted to a thumbnail.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_thumbnail
     */
    public function getThumbnail(string $path, string $format = 'jpeg', string $size = 'w64h64'): string
    {
        $dropboxApiArguments = [
            'path' => $this->normalizePath($path),
            'format' => $format,
            'size' => $size
        ];

        $response = $this->client->post('https://content.dropboxapi.com/2/files/get_thumbnail', [
            'headers' => [
                'Dropbox-API-Arg' => json_encode($dropboxApiArguments),
            ],
        ]);

        return (string) $response->getBody();
    }

    /**
     * Starts returning the contents of a folder. If the result's ListFolderResult.has_more field is true, call
     * list_folder/continue with the returned ListFolderResult.cursor to retrieve more entries.
     *
     * Note: auth.RateLimitError may be returned if multiple list_folder or list_folder/continue calls with same parameters
     * are made simultaneously by same API app for same user. If your app implements retry logic, please hold off the retry
     * until the previous request finishes.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder
     */
    public function listFolder(string $path = '', bool $recursive = false): array
    {
        $parameters = [
            'path' => $this->normalizePath($path),
            'recursive' => $recursive,
        ];

        return $this->requestRPC('files/list_folder', $parameters);
    }

    /**
     * Move a file or folder to a different location in the user's Dropbox.
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

        return $this->requestRPC('files/move', $parameters);
    }

    /**
     * Create a new file with the contents provided in the request.
     * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload
     */
    public function upload(string $path, string $mode, $contents): array
    {
        $dropboxApiArguments = [
            'path' => $this->normalizePath($path),
            'mode' => $mode,
        ];

        $response = $this->client->post('https://content.dropboxapi.com/2/files/upload', [
            'headers' => [
                'Dropbox-API-Arg' => json_encode($dropboxApiArguments),
                'Content-Type' => 'application/octet-stream',
            ],
            'body' => $contents,
        ]);

        $metadata = json_decode($response->getBody(), true);

        $metadata['.tag'] = 'file';

        return $metadata;
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path,'/');

        if ($path === '') {
            return '';
        }

        return '/'.$path;
    }

    protected function requestRPC(string $endpoint, array $parameters): array
    {
        $response = $this->client->post("https://api.dropboxapi.com/2/{$endpoint}", [
            'json' => $parameters
        ]);

        return json_decode($response->getBody(), true);
    }
}
