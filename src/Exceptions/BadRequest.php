<?php

namespace Spatie\Dropbox\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    /**
     * The dropbox error code supplied in the response.
     *
     * @var string|null
     */
    public $dropboxCode;

    public function __construct(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        if (isset($body['error']['.tag'])) {
            $this->dropboxCode = $body['error']['.tag'];
        }

        parent::__construct($body['error_summary']);
    }
}
