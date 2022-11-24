<?php

namespace Spatie\Dropbox\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    /**
     * @var \Psr\Http\Message\ResponseInterface
     */
    public $response;

    /**
     * The dropbox error code supplied in the response.
     *
     * @var string|null
     */
    public $dropboxCode;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;

        $content = (string) $response->getBody();
        $body = json_decode($content, true);

        if (strlen($content) > 0 && is_null($body)) {
            parent::__construct($content);
        } else if (! is_null($body)) {
            if (isset($body['error']['.tag'])) {
                $this->dropboxCode = $body['error']['.tag'];
            }

            parent::__construct($body['error_summary']);
        }
    }
}
