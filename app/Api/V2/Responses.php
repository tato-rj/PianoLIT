<?php

namespace App\Api\V2;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Responses
{
    public static function handles(Request $request): bool
    {
        return $request->is('api/v2', 'api/v2/*');
    }

    public function requestId(Request $request): string
    {
        if (!$request->attributes->has('api.v2.request_id')) {
            $request->attributes->set('api.v2.request_id', (string) Str::uuid());
        }
        return $request->attributes->get('api.v2.request_id');
    }

    public function success(Request $request, array $data, int $status = 200)
    {
        return $this->headers($request, response()->json([
            'data' => $data, 'meta' => ['request_id' => $this->requestId($request)],
        ], $status));
    }

    public function exception(Request $request, Throwable $exception)
    {
        $status = $exception instanceof \Illuminate\Contracts\Cache\LockTimeoutException ? 503
            : ($exception instanceof AuthenticationException ? 401
            : ($exception instanceof ValidationException ? 422
            : ($exception instanceof ModelNotFoundException ? 404
            : ($exception instanceof HttpExceptionInterface ? $exception->getStatusCode() : 500))));
        $errors = [
            400 => ['invalid_request', 'The request could not be read.'],
            401 => ['unauthenticated', 'Sign in to continue.'],
            403 => ['forbidden', 'This action is unavailable.'],
            404 => ['not_found', 'The requested resource was not found.'],
            405 => ['method_not_allowed', 'This HTTP method is unavailable.'],
            409 => ['conflict', 'Please refresh and try again.'],
            413 => ['request_too_large', 'The request is too large.'],
            415 => ['unsupported_media_type', 'Send a JSON request body.'],
            422 => ['invalid_request', 'Please check the request fields.'],
            429 => ['rate_limited', 'Too many requests. Please try again shortly.'],
            503 => ['temporarily_unavailable', 'Temporarily unavailable. Please try again.'],
        ];
        [$code, $message] = $errors[$status] ?? ['server_error', 'Something went wrong. Please try again.'];
        if ($exception instanceof Problem) {
            $code = $exception->errorCode;
            $message = $exception->getMessage();
        }
        $fields = $exception instanceof ValidationException ? (object) $exception->errors() : new \stdClass;
        $response = response()->json([
            'error' => ['code' => $code, 'message' => $message, 'fields' => $fields],
            'meta' => ['request_id' => $this->requestId($request)],
        ], $status);
        if ($exception instanceof HttpExceptionInterface) {
            foreach (['Retry-After', 'Allow'] as $header) {
                foreach ($exception->getHeaders() as $name => $value) {
                    if (strcasecmp($name, $header) === 0) $response->headers->set($header, $value);
                }
            }
        }
        if ($status === 401) $response->headers->set('WWW-Authenticate', 'Bearer');
        if ($status === 503 && !$response->headers->has('Retry-After')) $response->headers->set('Retry-After', '5');
        return $this->headers($request, $response);
    }

    public function headers(Request $request, $response)
    {
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Request-ID', $this->requestId($request));
        return $response;
    }
}
