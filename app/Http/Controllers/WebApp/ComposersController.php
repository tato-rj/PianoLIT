<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Composer;
use App\Services\WebApp\ComposerGroups;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ComposersController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'gender' => 'nullable|in:female', 'country' => 'nullable|integer|min:1',
            'composers' => ['nullable', Rule::in(array_keys(ComposerGroups::OPTIONS))],
        ]);
        $query = ComposerGroups::apply(Composer::atLeast(1), $request->input('composers'));
        $directoryTitle = ComposerGroups::OPTIONS[$request->input('composers')]['label'] ?? ($request->gender === 'female' ? 'Women composers' : 'Composers');
        if ($request->filled('gender')) $query->where('gender', $request->gender);
        if ($request->filled('country')) $query->where('country_id', $request->country);
        $composers = $query->get()->sortByDesc('pieces_count');

        // Only piece/collection names are needed for search; avoid serializing piece media.
        $composerWorks = DB::table('pieces')->whereIn('composer_id', $composers->pluck('id'))
            ->select('composer_id', 'name', 'collection_name')->get()->groupBy('composer_id');

        return view('webapp.composers.index', compact('composers', 'composerWorks', 'directoryTitle'));
    }

    public function show(Composer $composer)
    {
        $contemporaries = collect();

        if ($composer->date_of_birth && $composer->date_of_birth->lte(today())) {
            $born = $composer->date_of_birth;
            $end = $composer->date_of_death ?? today();
            $contemporaries = Composer::where('id', '!=', $composer->id)
                ->whereBetween('date_of_birth', [
                    $born->copy()->subYears(30)->startOfYear(),
                    $born->copy()->addYears(30)->endOfYear(),
                ])
                ->whereDate('date_of_birth', '<=', $end->min(today()))
                ->where(function ($query) use ($born) {
                    $query->whereNull('date_of_death')->orWhereDate('date_of_death', '>=', $born);
                })
                ->has('pieces')
                ->get()
                ->sort(function ($a, $b) use ($born) {
                    return [!$a->is_famous, abs($a->born_in - $born->year), $a->id]
                        <=> [!$b->is_famous, abs($b->born_in - $born->year), $b->id];
                })
                ->take(4)->sortBy('date_of_birth')->values();
        }

        return view('webapp.composers.show', compact('composer', 'contemporaries'));
    }
}
