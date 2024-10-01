<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{Composer, Piece};

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

        $composers = $results->take(20)->get();

        return response()->json(compact('composers'));
    }

    public function pieces(Request $request)
    {
        $strings = ['name', 'catalogue_name', 'catalogue_number', 'collection_name', 'collection_number', 'movement_number', 'description'];
        $booleans = [];
        $relationships = ['composer.name'];

        $results = Piece::query();

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

        if ($request->has('level'))
            $results->whereHas('tags', function($q) use ($request) {
                $q->where('name', 'LIKE', '%'.$request->level.'%');
            });

        $pieces = $results->take(20)->get();

        return response()->json(compact('pieces'));
    }
}
