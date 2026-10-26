<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Country, Pianist};

class PianistsController extends Controller
{
    public function index(Request $request)
    {
        $filters = array_merge(['search' => '', 'country' => ''], $request->validate([
            'search' => 'nullable|string|max:160', 'country' => 'nullable|integer|exists:countries,id',
        ]));
        $filters['search'] = trim((string) $filters['search']);
        $query = Pianist::query();
        if ($filters['country']) $query->where('country_id', $filters['country']);
        foreach (preg_split('/\s+/u', $filters['search'], -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($names) use ($like) {
                $names->whereRaw("LOWER(pianists.name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('country', function ($country) use ($like) {
                        $country->whereRaw("LOWER(name) LIKE ? ESCAPE '!'", [$like])
                            ->orWhereRaw("LOWER(nationality) LIKE ? ESCAPE '!'", [$like]);
                    });
            });
        }
        $pianists = $query->orderBy('name')->orderBy('id')->paginate(12)->withQueryString();
        $countries = Country::whereIn('id', Pianist::select('country_id'))->orderBy('name')->get();

        return view('pianists.index', compact('pianists', 'countries', 'filters'));
    }

    public function show(Pianist $pianist)
    {
        $morePianists = Pianist::where('id', '<>', $pianist->id)->orderBy('name')->orderBy('id')->limit(3)->get();

        return view('pianists.show', compact('pianist', 'morePianists'));
    }
}
