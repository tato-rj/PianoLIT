<?php

namespace App\Services\OpenAI;

class TextImprovementException extends \RuntimeException
{
    public function __construct(string $message, int $status = 502)
    {
        parent::__construct($message, $status);
    }
}
