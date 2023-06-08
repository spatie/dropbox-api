<?php

namespace Spatie\Dropbox;

class UploadSessionCursor
{
    public function __construct(
        /**
         * The upload session ID (returned by upload_session/start).
         */
        public string $session_id,
        /**
         * The amount of data that has been uploaded so far. We use this to make sure upload data isn't lost or duplicated in the event of a network error.
         */
        public int $offset = 0,
    ) {
    }
}
