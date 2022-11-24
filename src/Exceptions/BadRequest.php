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

        $ok = $this->_try_from_json($content);

        if (! $ok) $this->_try_from_plain_text($content);
    }

    /**
     * Try reading error from text.
     */
    private function _try_from_plain_text($content): bool {
        if (empty($content)) return false;
        parent::__construct($content);
        return true;
    }

    /**
     * Try reading error from json.
     */
    private function _try_from_json(string $content): bool {
        $content = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($body['error']['.tag'])) {
                $this->dropboxCode = $content['error']['.tag'];
            }

            parent::__construct($content['error_summary']);

            return true;
        }

        return false;
    }
}
