<?php

namespace Tests\Support;

use App\Exceptions\Handler;
use Illuminate\Contracts\Debug\ExceptionHandler;

trait ExceptionHandling {

    public function disableExceptionHandling()
    {
        $this->oldExceptionHandler = $this->app->make(ExceptionHandler::class);

        $this->app->instance(ExceptionHandler::class, new class extends Handler {
            public function __construct() {}
            public function report(\Exception $exception) {}
            public function render($request, \Exception $exception) {
                throw $exception;
            }
        });
    }

    public function withExceptionHandling()
    {
        $this->app->instance(ExceptionHandler::class, $this->oldExceptionHandler);
        return $this;
    }

}