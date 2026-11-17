<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Services\Api\V2\{ExploreResponse, Response};
use Illuminate\Http\Request;

class ExploreController extends Controller
{
    public function show(Request $request, ExploreResponse $explore)
    {
        return Response::data($explore->data($request->user()));
    }

    public function pieces(Request $request, ExploreResponse $explore)
    {
        $filters = $request->validate([
            'scope' => 'sometimes|required|string|in:catalogue,highlights',
            'tag_ids' => 'required_unless:scope,highlights|array|min:1|max:12',
            'tag_ids.*' => 'bail|required|string|regex:/^[1-9][0-9]{0,19}$/D|distinct',
            'page' => 'sometimes|required|integer|min:1|max:10000',
        ]);

        return Response::data($explore->pieces(
            $filters['scope'] ?? 'catalogue',
            $filters['tag_ids'] ?? [],
            (int) ($filters['page'] ?? 1)
        ));
    }
}
