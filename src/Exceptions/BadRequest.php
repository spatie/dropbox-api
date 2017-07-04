<?php

namespace Spatie\Dropbox\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    /**
     * The dropbox error code supplied in the response
     *
     * @var string
     */
    protected $dropboxCode = '';

    public function __construct(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        if ($response->getStatusCode() === 409) {
            $this->setDropboxCode($body['error']['.tag']);
        }

        parent::__construct($body['error_summary']);
    }

    protected function setDropboxCode(string $code)
    {
        $this->dropboxCode = $code;
    }

    /**
     * Returns the machine readable dropbox error code
     *
     * @return string
     */
    public function getDropboxCode(): string
    {
        return $this->dropboxCode;
    }
}
