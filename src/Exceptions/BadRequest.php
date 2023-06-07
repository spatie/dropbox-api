<?php

namespace Spatie\Dropbox\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    public ?string $dropboxCode = null;

    public function __construct(public ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        if ($body !== null) {
            if (isset($body['error']['.tag'])) {
                $this->dropboxCode = $body['error']['.tag'];
            }

            parent::__construct($body['error_summary']);
        }
    }
}
