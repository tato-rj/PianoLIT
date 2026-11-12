<?php

namespace App\Http\Controllers;

use App\Infograph\Infograph;
use App\Pianist;
use App\Services\Collections\Catalog;
use App\Services\Shop\Storefront;
use App\Services\WebApp\{AccessPolicy, PublicRepertoire};

class PublicSitemapsController extends Controller
{
    public function shop(Storefront $storefront)
    {
        return response()->view('components.seo.sitemap', ['urls' => $storefront->sitemapUrls()], 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function repertoire(PublicRepertoire $repertoire, AccessPolicy $policy, Catalog $collections)
    {
        $urls = collect([
            route('webapp.discover'),
            route('webapp.pieces.index'),
            route('webapp.playlists'),
            route('webapp.search.results'),
        ])->concat($repertoire->pieces()->select('pieces.id')->orderBy('id')->pluck('id')
            ->map(fn ($id) => route('webapp.pieces.show', $id)));

        if ($policy->cap('collections', null) !== 0) {
            $urls = $urls->concat($collections->entries()
                ->map(fn ($playlist) => route('webapp.playlists.show', $playlist)));
        }

        return response()->view('components.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function learnResources()
    {
        $urls = collect([
            route('tools.circle-of-fifths'),
            route('tools.chord-finder.index'),
            route('resources.infographs.index'),
            route('resources.pianists.index'),
        ])->concat(Infograph::published()->orderBy('id')->pluck('slug')
            ->map(fn ($slug) => route('resources.infographs.show', $slug)))
            ->concat(Pianist::orderBy('id')->pluck('slug')
                ->map(fn ($slug) => route('resources.pianists.show', $slug)))
            ->unique();

        return response()->view('components.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function metronome()
    {
        $urls = [route('metronome.home'), route('metronome.guide')];

        return response()->view('components.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
