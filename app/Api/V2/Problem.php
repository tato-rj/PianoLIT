<?php

namespace App\Api\V2;

use Symfony\Component\HttpKernel\Exception\HttpException;

class Problem extends HttpException
{
    public $errorCode;

    public function __construct(int $status, string $code, string $message)
    {
        parent::__construct($status, $message);
        $this->errorCode = $code;
    }
}
