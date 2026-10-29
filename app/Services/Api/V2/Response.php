<?php

namespace App\Services\Api\V2;

class Response
{
    public static function exception(\Throwable $error)
    {
        if ($error instanceof \Illuminate\Validation\ValidationException) {
            return self::error('invalid_request', 'Please check the highlighted fields.', 422, $error->errors());
        }
        if ($error instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return self::error('not_found', 'This item is unavailable.', 404);
        }
        if ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            $status = $error->getStatusCode();
            $response = self::error($status === 429 ? 'rate_limited' : 'request_failed',
                $status === 429 ? 'Too many requests. Please try again later.' : 'The request could not be completed.', $status);
            $response->headers->add($error->getHeaders());
            return $response;
        }
        return self::error('server_error', 'Please try again later.', 500);
    }

    public static function data(array $data)
    {
        return response()->json(['data' => $data, 'meta' => self::meta()]);
    }

    public static function error(string $code, string $message, int $status, array $fields = [])
    {
        return response()->json(['error' => ['code' => $code, 'message' => $message, 'fields' => (object) $fields], 'meta' => self::meta()], $status);
    }

    private static function meta(): array
    {
        if (!request()->attributes->has('api_v2_request_id')) {
            request()->attributes->set('api_v2_request_id', (string) \Illuminate\Support\Str::uuid());
        }
        return ['request_id' => request()->attributes->get('api_v2_request_id')];
    }
}
