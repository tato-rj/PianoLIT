<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2\{FavoriteFailure, FavoriteFolders, Response};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteFoldersController extends Controller
{
    public function index(Request $request, FavoriteFolders $folders)
    {
        $input = Validator::make($request->query(), ['piece_id' => 'required|string|regex:/^[1-9][0-9]*$/D'])->validate();
        return Response::data($folders->listing($request->user(), $input['piece_id']));
    }

    public function save(Request $request, string $folder, string $piece, FavoriteFolders $folders)
    {
        $this->emptyPayload($request);
        return Response::data($folders->change($request->user(), $folder, $piece, true));
    }

    public function remove(Request $request, string $folder, string $piece, FavoriteFolders $folders)
    {
        $this->emptyPayload($request);
        return Response::data($folders->change($request->user(), $folder, $piece, false));
    }

    public function store(Request $request, FavoriteFolders $folders)
    {
        $input = $this->objectPayload($request);
        if (array_diff(array_keys($input), ['name', 'piece_id'])) throw new FavoriteFailure('invalid_request', 'Only name and optional piece_id are accepted.', 422);
        if (isset($input['name']) && is_string($input['name'])) $input['name'] = trim($input['name']);
        $input = Validator::make($input, ['name' => 'required|string|min:3|max:36',
            'piece_id' => 'sometimes|required|string|regex:/^[1-9][0-9]*$/D'])->validate();
        $key = $request->header('Idempotency-Key');
        if ($key !== null) {
            Validator::make(['Idempotency-Key' => $key], ['Idempotency-Key' => 'required|uuid'])->validate();
            $key = strtolower($key);
        }
        return Response::data($folders->create($request->user(), $input, $key), 201);
    }

    private function emptyPayload(Request $request): void
    {
        if ($request->getContent() !== '' && $this->objectPayload($request) !== []) throw new FavoriteFailure('invalid_request', 'This operation accepts no request fields.', 422);
    }

    private function objectPayload(Request $request): array
    {
        $json = json_decode($request->getContent());
        if (!$request->isJson() || !$json instanceof \stdClass) throw new FavoriteFailure('invalid_request', 'Send a JSON object with Content-Type: application/json.', 422);
        return (array) $json;
    }
}
