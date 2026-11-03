<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2\{MatchTourFailure, MatchTourMedia, MatchTours, Response};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;

class MatchTourController extends Controller
{
    public function start(Request $request, MatchTours $tours)
    {
        $input = $this->payload($request, ['request_id' => 'required|uuid']);
        return Response::data($tours->start($request->user(), strtolower($input['request_id'])));
    }

    public function show(Request $request, string $tour, MatchTours $tours)
    {
        return Response::data($tours->load($request->user(), $tour));
    }

    public function answer(Request $request, string $tour, string $slot, MatchTours $tours)
    {
        $input = $this->payload($request, ['request_id' => 'required|uuid', 'revision' => 'required|integer|min:0', 'value' => 'present|nullable|string|max:64']);
        // The contract uses JSON integer revisions and string IDs, not coercion.
        if (!is_int($input['revision'])) throw new MatchTourFailure('invalid_answer', 'revision must be a JSON integer.', 422);
        $input['request_id'] = strtolower($input['request_id']);
        return Response::data($tours->answer($request->user(), $tour, $slot, $input));
    }

    public function result(Request $request, string $tour, MatchTours $tours)
    {
        return Response::data($tours->result($request->user(), $tour));
    }

    public function media(Request $request, string $asset, string $kind, MatchTourMedia $media)
    {
        if (!URL::hasValidSignature($request, false)) throw new MatchTourFailure('media_url_expired', 'Reload the tour for a fresh sample URL.', 403);
        return response()->file($media->file($asset, $kind), ['Content-Type' => $kind === 'audio' ? 'audio/mpeg' : 'image/png',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer']);
    }

    private function payload(Request $request, array $rules): array
    {
        // Preserve explicit JSON null for skip; global browser normalization turns
        // an empty string into null and must not silently turn it into a skip.
        $json = json_decode($request->getContent());
        if (!$request->isJson() || !$json instanceof \stdClass) {
            throw new MatchTourFailure('invalid_request', 'Send a JSON object with Content-Type: application/json.', 422);
        }
        return Validator::make((array) $json, $rules)->validate();
    }
}
