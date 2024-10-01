<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composer(Request $request)
    {
        $results = Composer::query();

        if ($request->has('name'))
            $results->where('name', 'LIKE', '%'.$request->name.'%');

        if ($request->has('country'))
            $results->whereHas('country', function($q) use ($request) {
                $q->where('name', 'LIKE', '%'.$request->country.'%');
            });

        $composers = $results->take(5)->get();

        return response()->json(compact('results'));
    }

}
