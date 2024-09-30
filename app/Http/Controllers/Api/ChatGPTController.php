<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        // Start the query
        $query = Composer::query();

        // Filter by name (partial match)
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }

        // Filter by biography (partial match)
        if ($request->has('biography')) {
            $query->where('biography', 'like', '%' . $request->input('biography') . '%');
        }

        // Filter by gender
        if ($request->has('gender')) {
            $query->where('gender', $request->input('gender'));
        }

        // Filter by ethnicity (nullable)
        if ($request->has('ethnicity')) {
            $query->where('ethnicity', $request->input('ethnicity'));
        }

        // Filter by curiosity (nullable, partial match)
        if ($request->has('curiosity')) {
            $query->where('curiosity', 'like', '%' . $request->input('curiosity') . '%');
        }

        // Filter by period
        if ($request->has('period')) {
            $query->where('period', $request->input('period'));
        }

        // Filter by country_id (nullable)
        if ($request->has('country_id')) {
            $query->where('country_id', $request->input('country_id'));
        }

        // Filter by is_famous (boolean)
        if ($request->has('is_famous')) {
            $query->where('is_famous', $request->input('is_famous') === 'true');
        }

        // Filter by is_pedagogical (boolean)
        if ($request->has('is_pedagogical')) {
            $query->where('is_pedagogical', $request->input('is_pedagogical') === 'true');
        }

        // Filter by mood (nullable)
        if ($request->has('mood')) {
            $query->where('mood', $request->input('mood'));
        }

        // Filter by date_of_birth (nullable)
        if ($request->has('born_after')) {
            $query->whereYear('date_of_birth', '>=', $request->input('born_after'));
        }

        if ($request->has('born_before')) {
            $query->whereYear('date_of_birth', '<=', $request->input('born_before'));
        }

        // Filter by date_of_death (nullable)
        if ($request->has('died_after')) {
            $query->where('date_of_death', '>=', $request->input('died_after'));
        }

        if ($request->has('died_before')) {
            $query->where('date_of_death', '<=', $request->input('died_before'));
        }

        // Execute the query and return the results
        $results = $query->get();

        return response()->json($results);
    }

}
