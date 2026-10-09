<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\Api;
use App\{Composer, Country, Piece, Tag};
use Illuminate\Validation\Rule;
use App\Services\WebApp\{ComposerGroups, ExploreCatalogue};
use App\Services\RecentlyViewedPieces;
use App\Services\WebApp\PieceCards;
use App\Services\WebApp\GalleryGradients;

class TabsController extends Controller
{
    public function discover(Api $api, RecentlyViewedPieces $recentlyViewed)
    {
        $composers = Composer::atLeast(1)->get()->sortBy('last_name')->values();
        $rows = $api->for('webapp')->discover();

        // Replace this band only on the web; leave the shared/mobile feed intact.
        $rows = $rows->map(function ($row) use ($api) {
            if ($row['title'] === 'From black composers') {
                $row['title'] = 'Past highlights';
                $row['content'] = Piece::freePicks()->free(false)
                    ->where('highlighted_at', '<=', now())->orderByDesc('id')->with('composer')->take(7)->get();
                $api->withAttributes($row['content'], ['type' => 'piece', 'source' => route('api.pieces.find')]);
            }
            return $row;
        });

        if (auth('web')->check()) {
            $pieces = $recentlyViewed->forUser(auth('web')->user());
            if ($pieces->isNotEmpty()) {
                $api->order(2)->withAttributes($pieces, ['type' => 'piece', 'source' => route('api.pieces.find')]);
                $position = $rows->search(function ($row) { return $row['title'] === 'Latest pieces'; });
                // Personal rows must stay outside the shared discovery cache.
                $rows->splice($position === false ? 2 : $position, 0, [[
                    'title' => 'Recently viewed',
                    'row' => 'gallery',
                    'type' => 'piece',
                    'content' => $pieces,
                ]]);
            }
        }

        // Clone cached models before adding any request-local presentation data.
        $cards = [];
        $galleryIndex = 0;
        $rows = $rows->map(function ($row) use (&$cards, &$galleryIndex) {
            $isGallery = ($row['row'] ?? null) === 'gallery';
            $color = $isGallery && collect($row['content'])->isNotEmpty()
                ? GalleryGradients::at($galleryIndex++) : null;
            if ($isGallery || ($row['type'] ?? null) === 'piece') {
                $row['content'] = collect($row['content'])->map(function ($card) use (&$cards, $color) {
                    $card = clone $card;
                    if ($color) $card->color = $color;
                    if ($card instanceof Piece) $cards[] = $card;
                    return $card;
                });
            }
            return $row;
        });
        PieceCards::load($cards, false);

        return view('webapp.discover.index', compact(['rows', 'composers']));
    }

    public function latest(Request $request, Api $api)
    {
        $request->validate(['page' => 'nullable|integer|min:1']);
        $pieces = Piece::with('tags')->withVideos()->latest()->orderByDesc('id')->paginate(12);
        PieceCards::load($pieces->getCollection(), false);
        $api->withAttributes($pieces, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return view('webapp.discover.latest', compact('pieces'));
    }

    public function explore(Request $request, ExploreCatalogue $catalogue)
    {
        return view('webapp.explore.index', $catalogue->data($request));
    }

    public function highlights(Api $api, Request $request, ExploreCatalogue $catalogue)
    {
        $validated = $request->validate([
            'explore' => 'nullable|array:level,mood,tag,composers,country',
            'explore.level' => ['nullable', Rule::in(ExploreCatalogue::LEVELS)],
            'explore.mood' => ['nullable', Rule::in(array_keys(ExploreCatalogue::MOODS))],
            'explore.tag' => 'nullable|integer|min:1',
            'explore.composers' => ['nullable', Rule::in(array_keys(ComposerGroups::OPTIONS))],
            'explore.country' => 'nullable|integer|min:1',
            'filters' => 'nullable|array|max:5',
            'filters.*' => ['bail', 'required', 'string', 'max:1024', function ($attribute, $value, $fail) {
                $names = json_decode($value);
                if (!is_array($names) || array_values($names) !== $names || count($names) > 6) {
                    return $fail('Invalid filters.');
                }
                foreach ($names as $name) {
                    if (!is_string($name) || strlen($name) > 64) return $fail('Invalid filters.');
                }
            }],
        ]);
        $explore = array_filter($validated['explore'] ?? [], function ($value) { return $value !== null && $value !== ''; });

        // All card metadata stays available for the existing client-side sorting.
        // Only the popularity count is used here; omit the other default counts
        // and the composer's country relationship from this web-only query.
        $pieces = Piece::freePicks(false)->select('pieces.*')->withCount('views')
            ->with(['tags', 'composer' => function ($query) {
                $query->select('composers.*')->setEagerLoads([]);
            }])->filtered();
        if ($explore) $pieces->whereIn('pieces.id', $catalogue->query($explore)->select('pieces.id')->setEagerLoads([])->withCount([]));
        $pieces = $pieces->get();

        if ($request->wantsJson()) {
            return view('webapp.highlights.pieces', compact('pieces'))->render();
        }

        $exploreLabels = collect($explore)->map(function ($value, $facet) {
            if ($facet === 'level') return ucwords($value);
            if ($facet === 'mood') return ExploreCatalogue::MOODS[$value]['label'];
            if ($facet === 'composers') return ComposerGroups::OPTIONS[$value]['label'];
            if ($facet === 'country') return optional(Country::find($value))->name;
            return ucfirst(optional(Tag::whereIn('type', ['technique', 'period', 'genre'])->find($value))->name ?? 'Selected tag');
        })->filter()->implode(' · ');
        return view('webapp.highlights.index', compact('pieces', 'explore', 'exploreLabels'));
    }

    public function playlists(\App\Services\WebApp\Collections $collections)
    {
        return view('webapp.playlists.index', $collections->data());
    }

    public function tour(Request $request, \App\Services\WebApp\MatchTour $tour)
    {
        if ($request->wantsJson()) {
            $data = $tour->data();
            return response()->json(['html' => view('webapp.tour.content', ['tour' => $data])->render(), 'tour' => $data])
                ->header('Cache-Control', 'no-store, private');
        }

        return view('webapp.tour.index');
    }

    public function myPieces()
    {
        $user = auth('web')->user();
        $folders = $user ? $user->favoriteFolders()->alphabetical('name')->get() : collect();
        $folderPreviews = collect();
        $folderSearch = collect();
        if ($user) {
            // Suggestions already need these favorites. Keep folder data on this web-only load.
            $user->setRelation('favorites', $user->favorites()
                ->withPivot('favorite_folder_id', 'order')
                ->with('tags')
                ->get());
            $folderPieces = $user->favorites
                ->filter(function ($piece) { return $piece->pivot->favorite_folder_id !== null; })
                ->groupBy(function ($piece) { return $piece->pivot->favorite_folder_id; });
            $folderPreviews = $folderPieces->map(function ($pieces) {
                return $pieces->sortBy(function ($piece) { return $piece->pivot->order; })->take(2);
            });
            $folderSearch = $folderPieces->map(function ($pieces) {
                return $pieces->map(function ($piece) {
                    return implode(' ', [$piece->name, $piece->short_name, $piece->composer->name, $piece->composer->short_name]);
                })->implode(' ');
            });
        }
        $suggestions = $user ? PieceCards::load($user->suggestions(20)->shuffle()->take(10)) : collect();

        return view('webapp.user.my-pieces.index', compact('folders', 'folderPreviews', 'folderSearch', 'suggestions'));
    }

    public function settings()
    {      
    	return view('webapp.settings.index');
    }
}
