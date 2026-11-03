<?php

namespace App\Services\Api\V2;

class MatchTourFailure extends \RuntimeException
{
    public $errorCode, $status;

    public function __construct(string $code, string $message, int $status)
    {
        parent::__construct($message);
        $this->errorCode = $code;
        $this->status = $status;
    }
}
