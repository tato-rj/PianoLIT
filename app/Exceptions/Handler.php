<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($this->onWebapp($request)) {
            if ($this->codeIs(404, $exception))
                return redirect(route('webapp.discover'));

            if ($this->codeIs(402, $exception))
                return redirect(route('webapp.membership.pricing'));

            if ($this->codeIs(403, $exception) && $this->messageIs('You already have a membership account', $exception))
                return redirect()->back()->with('error', 'Your account is already associated with an In-App Purchase subscription with Apple.');   
        }

        return parent::render($request, $exception);
    }

    /**
     * Convert an authentication exception into a response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Auth\AuthenticationException  $exception
     * @return \Illuminate\Http\Response
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        $guard = array_get($exception->guards(), 0);

        switch ($guard) {
            case 'admin':
                $login = 'admin.login.show';
                break;
            
            default:
                $login = 'login';
                break;
        }

        return $request->expectsJson()
                    ? response()->json(['message' => $exception->getMessage()], 401)
                    : redirect()->guest(route($login));
    }

    protected function onWebapp($request)
    {
        return 'my' == explode('.', $request->getHttpHost())[0];
    }

    public function codeIs($code, $exception)
    {
        return method_exists($exception, 'getStatusCode') && $exception->getStatusCode() == $code;
    }

    public function messageIs($message, $exception)
    {
        return method_exists($exception, 'getMessage') && $exception->getMessage() == $message;
    }
}
