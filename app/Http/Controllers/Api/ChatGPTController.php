<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        $strings = ['name', 'ethnicity', 'period'];
        $booleans = ['is_famous'];
        $relationships = ['country.name'];

        $results = Composer::query();

        foreach ($strings as $field) {
            if ($request->has($field))
                $results->where($field, 'LIKE', '%'.$request->$field.'%');
        }

        foreach ($booleans as $field) {
            if ($request->has($field))
                $results->where($field, $request->$field);
        }

        foreach ($relationships as $relation => $field) {
            if ($request->has($relation))
                $results->whereHas($relation, function($q) use ($request, $relation, $field) {
                    $q->where($field, 'LIKE', '%'.$request->$relation.'%');
                });
        }

        $composers = $results->take(5)->get();

        return response()->json(compact('composers'));
    }

}
