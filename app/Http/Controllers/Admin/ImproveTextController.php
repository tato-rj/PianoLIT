<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OpenAI\{TextImprover, TextImprovementException};
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ImproveTextController extends Controller
{
    public function __invoke(Request $request, TextImprover $improver)
    {
        $data = $request->validate([
            'texts' => 'required|array|min:1|max:1000',
            'texts.*' => 'required|string|max:50000',
            'length' => 'sometimes|required|in:shorter,same,longer',
            'tone' => 'sometimes|required|in:casual,same,formal',
            'max_length' => 'sometimes|nullable|integer|min:1|max:50000',
        ]);
        if (array_keys($data['texts']) !== range(0, count($data['texts']) - 1)
            || mb_strlen(implode('', $data['texts'])) > 50000) {
            throw ValidationException::withMessages(['texts' => 'Use a text list with at most 50000 characters in total.']);
        }

        try {
            return response()->json(['texts' => $improver->generate(
                $data['texts'], $data['length'] ?? 'same', $data['tone'] ?? 'same', $data['max_length'] ?? null
            )]);
        } catch (TextImprovementException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }
    }
}
