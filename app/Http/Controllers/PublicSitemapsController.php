<?php

namespace App\Http\Controllers;

use App\Infograph\Infograph;
use App\Pianist;

class PublicSitemapsController extends Controller
{
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
