<?php

namespace Spatie\Dropbox;

use Exception;
use GuzzleHttp\Psr7\StreamWrapper;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\ClientException;
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

    const MAX_CHUNK_SIZE = 150 * 1024 * 1024;

    /** @var string */
    protected $accessToken;

    /** @var \GuzzleHttp\Client */
    protected $client;

    public function __construct(string $accessToken, GuzzleClient $client = null)
    {
        $this->accessToken = $accessToken;

        $this->client = $client ?? new GuzzleClient([
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
    public function copy(string $fromPath, string $toPath): array
    {
        $parameters = [
            'from_path' => $this->normalizePath($fromPath),
            'to_path' => $this->normalizePath($toPath),
        ];

        return $this->rpcEndpointRequest('files/copy', $parameters);
    }

    /**
     * Create a folder at a given path.
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
     * Create a shared link with custom settings.
     *
     * If no settings are given then the default visibility is RequestedVisibility.public.
     * The resolved visibility, though, may depend on other aspects such as team and
     * shared folder settings). Only for paid users.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#sharing-create_shared_link_with_settings
     */
    public function createSharedLinkWithSettings(string $path, array $settings = [])
    {
        $parameters = [
            'path' => $this->normalizePath($path),
            'settings' => $settings,
        ];

        return $this->rpcEndpointRequest('sharing/create_shared_link_with_settings', $parameters);
    }

    /**
     * List shared links.
     *
     * For empty path returns a list of all shared links. For non-empty path
     * returns a list of all shared links with access to the given path.
     *
     * If direct_only is set true, only direct links to the path will be returned, otherwise
     * it may return link to the path itself and parent folders as described on docs.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#sharing-list_shared_links
     */
    public function listSharedLinks(string $path = null, bool $direct_only = false, string $cursor = null): array
    {
        $parameters = [
            'path' => $path ? $this->normalizePath($path) : null,
            'cursor' => $cursor,
            'direct_only' => $direct_only,
        ];

        $body = $this->rpcEndpointRequest('sharing/list_shared_links', $parameters);

        return $body['links'];
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
     * This method currently supports files with the following file extensions:
     * jpg, jpeg, png, tiff, tif, gif and bmp.
     *
     * Photos that are larger than 20MB in size won't be converted to a thumbnail.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-get_thumbnail
     */
    public function getThumbnail(string $path, string $format = 'jpeg', string $size = 'w64h64'): string
    {
        $arguments = [
            'path' => $this->normalizePath($path),
            'format' => $format,
            'size' => $size,
        ];

        $response = $this->contentEndpointRequest('files/get_thumbnail', $arguments);

        return (string) $response->getBody();
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
     * Once a cursor has been retrieved from list_folder, use this to paginate through all files and
     * retrieve updates to the folder, following the same rules as documented for list_folder.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-list_folder-continue
     */
    public function listFolderContinue(string $cursor = ''): array
    {
        return $this->rpcEndpointRequest('files/list_folder/continue', compact('cursor'));
    }

    /**
     * Move a file or folder to a different location in the user's Dropbox.
     *
     * If the source path is a folder all its contents will be moved.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-move_v2
     */
    public function move(string $fromPath, string $toPath): array
    {
        $parameters = [
            'from_path' => $this->normalizePath($fromPath),
            'to_path' => $this->normalizePath($toPath),
        ];

        return $this->rpcEndpointRequest('files/move_v2', $parameters);
    }

    /**
     * The file should be uploaded in chunks if it size exceeds the 150 MB threshold
     * or if the resource size could not be determined (eg. a popen() stream).
     *
     * @param string|resource $contents
     *
     * @return bool
     */
    protected function shouldUploadChunked($contents): bool
    {
        $size = is_string($contents) ? strlen($contents) : fstat($contents)['size'];

        if ($this->isPipe($contents)) {
            return true;
        }

        if ($size === null) {
            return true;
        }

        return $size > static::MAX_CHUNK_SIZE;
    }

    /**
     * Check if the contents is a pipe stream (not seekable, no size defined).
     *
     * @param string|resource $contents
     *
     * @return bool
     */
    protected function isPipe($contents): bool
    {
        return is_resource($contents) ? (fstat($contents)['mode'] & 010000) != 0 : false;
    }

    /**
     * Create a new file with the contents provided in the request.
     *
     * Do not use this to upload a file larger than 150 MB. Instead, create an upload session with upload_session/start.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload
     *
     * @param string $path
     * @param string|resource $contents
     * @param string|array $mode
     *
     * @return array
     */
    public function upload(string $path, $contents, $mode = 'add'): array
    {
        if ($this->shouldUploadChunked($contents)) {
            return $this->uploadChunked($path, $contents, $mode);
        }

        $arguments = [
            'path' => $this->normalizePath($path),
            'mode' => $mode,
        ];

        $response = $this->contentEndpointRequest('files/upload', $arguments, $contents);

        $metadata = json_decode($response->getBody(), true);

        $metadata['.tag'] = 'file';

        return $metadata;
    }

    /**
     * Upload file split in chunks. This allows uploading large files, since
     * Dropbox API v2 limits the content size to 150MB.
     *
     * The chunk size will affect directly the memory usage, so be careful.
     * Large chunks tends to speed up the upload, while smaller optimizes memory usage.
     *
     * @param string          $path
     * @param string|resource $contents
     * @param string          $mode
     * @param int             $chunkSize
     *
     * @return array
     */
    public function uploadChunked(string $path, $contents, $mode = 'add', $chunkSize = null): array
    {
        $chunkSize = $chunkSize ?? static::MAX_CHUNK_SIZE;
        $stream = $contents;

        // This method relies on resources, so we need to convert strings to resource
        if (is_string($contents)) {
            $stream = fopen('php://memory', 'r+');
            fwrite($stream, $contents);
            rewind($stream);
        }

        $data = self::readChunk($stream, $chunkSize);
        $cursor = null;

        while (! ((strlen($data) < $chunkSize) || feof($stream))) {
            // Start upload session on first iteration, then just append on subsequent iterations
            $cursor = isset($cursor) ? $this->uploadSessionAppend($data, $cursor) : $this->uploadSessionStart($data);
            $data = self::readChunk($stream, $chunkSize);
        }

        // If there's no cursor here, our stream is small enough to a single request
        if (! isset($cursor)) {
            $cursor = $this->uploadSessionStart($data);
            $data = '';
        }

        return $this->uploadSessionFinish($data, $cursor, $path, $mode);
    }

    /**
     * Upload sessions allow you to upload a single file in one or more requests,
     * for example where the size of the file is greater than 150 MB.
     * This call starts a new upload session with the given data.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-start
     *
     * @param string $contents
     * @param bool   $close
     *
     * @return UploadSessionCursor
     */
    public function uploadSessionStart($contents, bool $close = false): UploadSessionCursor
    {
        $arguments = compact('close');

        $response = json_decode(
            $this->contentEndpointRequest('files/upload_session/start', $arguments, $contents)->getBody(),
            true
        );

        return new UploadSessionCursor($response['session_id'], strlen($contents));
    }

    /**
     * Append more data to an upload session.
     * When the parameter close is set, this call will close the session.
     * A single request should not upload more than 150 MB.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-append_v2
     *
     * @param string              $contents
     * @param UploadSessionCursor $cursor
     * @param bool                $close
     *
     * @return \Spatie\Dropbox\UploadSessionCursor
     */
    public function uploadSessionAppend($contents, UploadSessionCursor $cursor, bool $close = false): UploadSessionCursor
    {
        $arguments = compact('cursor', 'close');

        $this->contentEndpointRequest('files/upload_session/append_v2', $arguments, $contents);

        $cursor->offset += strlen($contents);

        return $cursor;
    }

    /**
     * Finish an upload session and save the uploaded data to the given file path.
     * A single request should not upload more than 150 MB.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#files-upload_session-finish
     *
     * @param string                              $contents
     * @param \Spatie\Dropbox\UploadSessionCursor $cursor
     * @param string                              $path
     * @param string|array                        $mode
     * @param bool                                $autorename
     * @param bool                                $mute
     *
     * @return array
     */
    public function uploadSessionFinish($contents, UploadSessionCursor $cursor, string $path, $mode = 'add', $autorename = false, $mute = false): array
    {
        $arguments = compact('cursor');
        $arguments['commit'] = compact('path', 'mode', 'autorename', 'mute');

        $response = $this->contentEndpointRequest(
            'files/upload_session/finish',
            $arguments,
            ($contents == '') ? null : $contents
        );

        $metadata = json_decode($response->getBody(), true);

        $metadata['.tag'] = 'file';

        return $metadata;
    }

    /**
     * Sometimes fread() returns less than the request number of bytes (for example, when reading
     * from network streams).  This function repeatedly calls fread until the requested number of
     * bytes have been read or we've reached EOF.
     *
     * @param resource $stream
     * @param int      $chunkSize
     *
     * @throws Exception
     * @return string
     */
    protected static function readChunk($stream, int $chunkSize)
    {
        $chunk = '';
        while (! feof($stream) && $chunkSize > 0) {
            $part = fread($stream, $chunkSize);
            if ($part === false) {
                throw new Exception('Error reading from $stream.');
            }
            $chunk .= $part;
            $chunkSize -= strlen($part);
        }

        return $chunk;
    }

    /**
     * Get Account Info for current authenticated user.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#users-get_current_account
     *
     * @return array
     */
    public function getAccountInfo(): array
    {
        return $this->rpcEndpointRequest('users/get_current_account');
    }

    /**
     * Revoke current access token.
     *
     * @link https://www.dropbox.com/developers/documentation/http/documentation#auth-token-revoke
     */
    public function revokeToken()
    {
        $this->rpcEndpointRequest('auth/token/revoke');
    }

    protected function normalizePath(string $path): string
    {
        $path = trim($path, '/');

        return ($path === '') ? '' : '/'.$path;
    }

    /**
     * @param string $endpoint
     * @param array $arguments
     * @param string|resource $body
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws \Exception
     */
    public function contentEndpointRequest(string $endpoint, array $arguments, $body = ''): ResponseInterface
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
            throw $this->determineException($exception);
        }

        return $response;
    }

    public function rpcEndpointRequest(string $endpoint, array $parameters = null): array
    {
        try {
            $options = [];

            if ($parameters) {
                $options['json'] = $parameters;
            }

            $response = $this->client->post("https://api.dropboxapi.com/2/{$endpoint}", $options);
        } catch (ClientException $exception) {
            throw $this->determineException($exception);
        }

        $response = json_decode($response->getBody(), true);

        return $response ?? [];
    }

    protected function determineException(ClientException $exception): Exception
    {
        if (in_array($exception->getResponse()->getStatusCode(), [400, 409])) {
            return new BadRequest($exception->getResponse());
        }

        return $exception;
    }
}
