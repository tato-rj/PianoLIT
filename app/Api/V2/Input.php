<?php

namespace App\Api\V2;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Input
{
    public static function validate(Request $request, array $rules = []): array
    {
        // Credentials and bearer tokens must never be accepted from a URL.
        if ($request->query->count()) self::unsupported();
        $content = $request->getContent();
        $data = [];
        if ($content !== '') {
            if (!$request->isJson()) throw new Problem(415, 'unsupported_media_type', 'Send a JSON request body.');
            try {
                $object = json_decode($content, false, 32, JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw new Problem(400, 'invalid_json', 'The JSON request could not be read.');
            }
            if (!$object instanceof \stdClass) {
                throw ValidationException::withMessages(['request' => ['Send a JSON object.']]);
            }
            // Reuse Laravel's trimmed/null-normalized JSON bag, while preserving
            // the password exceptions in TrimStrings. The raw parse above only
            // establishes valid JSON and the required object shape.
            $data = $request->json()->all();
        }
        if (array_diff(array_keys($data), array_keys($rules))) self::unsupported();
        return Validator::make($data, $rules)->validate();
    }

    private static function unsupported(): void
    {
        throw ValidationException::withMessages(['request' => ['Unsupported request fields. Do not submit account IDs or tokens in request fields.']]);
    }
}
