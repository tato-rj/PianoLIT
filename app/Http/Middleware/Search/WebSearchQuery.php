<?php

namespace App\Http\Middleware\Search;

use Closure;
use Illuminate\Http\Request;

class WebSearchQuery
{
    public function handle(Request $request, Closure $next)
    {
        $query = $request->query();
        $hasLegacyTerm = array_key_exists('search', $query);
        $hasTerm = array_key_exists('for', $query);
        $term = $hasTerm ? $query['for'] : ($query['search'] ?? null);
        $term = is_string($term) ? trim($term) : null;

        // Keep bookmarked pages readable and let existing Ajax clients request fragments directly.
        if ($request->routeIs('webapp.search.results') && !$request->wantsJson()
            && ($hasLegacyTerm || array_key_exists('lazy-load', $query))) {
            unset($query['search'], $query['for'], $query['lazy-load']);
            if ($term !== null && $term !== '') $query = ['for' => $term] + $query;

            return redirect()->route('webapp.search.results', $query, 301);
        }

        if ($hasTerm || $hasLegacyTerm) {
            // The shared search engine and exact-match middleware use the original input key.
            $request->merge(['search' => $term]);
        }

        return $next($request);
    }
}
