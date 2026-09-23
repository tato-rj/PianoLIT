<?php

namespace App\Api;

use App\{Piece, Tag, Composer};
use Illuminate\Validation\Rule;

class Search
{
	protected $request, $options, $query;
	protected $lateFilter = false;

    public function __construct($request)
    {
        $request->validate([
            'search' => 'nullable|string',
            'model' => ['nullable', Rule::in([Tag::class, Composer::class])],
            'filters' => 'nullable|array',
            'filters.*' => ['string', 'json', function ($attribute, $value, $fail) {
                $tags = json_decode($value, true);
                if (! is_array($tags) || array_filter($tags, function ($tag) { return ! is_string($tag); })) {
                    $fail('Filters must contain a JSON array of tag names.');
                }
            }],
            'page' => 'nullable|integer|min:0',
        ]);

        $this->request = $request;

        $this->options = $request->has('lazy-load') ? ['hitsPerPage' => 10, 'page' => $request->page ?? 0] : [];

        $this->request->is_empty = strlen($request->search ?? '') <= 2;

        return $this;
    }

	public function query()
	{
        if ($this->request->is_empty)
            return $this;

        if ($model = $this->request->model) {
            $match = $this->request->attributes->get('webapp_search_match')
                ?? (new $model)->name($this->request->search)->first();
            $this->query = $match ? $match->pieces()->latest() : Piece::whereRaw('1 = 0');
        } else {
            $this->query = Piece::search($this->request->search);
        }

        return $this;       
	}

	public function filtered()
	{
		if (! $this->query || ! $this->request->filters)
			return $this;

		if ($this->query instanceof \Laravel\Scout\Builder) {
			$this->lateFilter = true;
		} else {
			foreach ($this->request->filters as $list) {
				$this->query->whereHas('tags', function($q) use ($list) {
					return $q->whereIn('name', json_decode($list));
				});	
			}
		}

		return $this;
	}

    public function get()
    {
        if (! $this->query)
            return $this->request->has('count') ? response()->json(['count' => 0]) : null;

        if ($this->request->has('count'))
            return response()->json(['count' => $this->query->count()]);

        $pieces = $this->options ? $this->query->paginate($this->options['hitsPerPage']) : $this->query->get();

        $pieces = $pieces->load(['tags', 'composer', 'favorites']);

        if ($this->lateFilter) {
        	foreach ($pieces as $index => $piece) {
        		$notInList = false;

        		foreach ($this->request->filters as $list) {
	        		if ($piece->tags_array->intersect(json_decode($list))->isEmpty()) {
	        			$notInList = true;
	        			break;
	        		}
        		}

        		if ($notInList)
	        		$pieces->forget($index);
        	}
        }

        $pieces->each(function($piece) {
            $piece->isFavorited($this->getUserId());
        });

        return $pieces;
    }

    // Browser rendering needs only card data; keep the mobile get() contract intact.
    public function forWebApp()
    {
        if (! $this->query) return collect();

        $guest = ! auth('web')->check();
        if ($guest && $this->lateFilter) {
            // Filters run after Scout hydration. Scan in relevance order until the
            // first three matching results are found, even across backend pages.
            $pieces = new \Illuminate\Database\Eloquent\Collection;
            $page = 1;
            do {
                $batch = $this->query->paginate(50, 'page', $page++);
                $matches = $batch->getCollection()->loadMissing('tags');
                $pieces = $pieces->merge($this->filterWebPieces($matches))->take(3);
            } while ($pieces->count() < 3 && $batch->hasMorePages());
        } else {
            $pieces = $guest ? $this->query->take(3)->get()
                : ($this->options ? $this->query->simplePaginate(10)->getCollection() : $this->query->get());
            if ($this->lateFilter) {
                $pieces = $this->filterWebPieces($pieces->loadMissing('tags'));
            }
        }

        return \App\Services\WebApp\PieceCards::load($pieces);
    }

    protected function filterWebPieces($pieces)
    {
        return $pieces->filter(function ($piece) {
            foreach ($this->request->filters as $list) {
                if ($piece->tags_array->intersect(json_decode($list))->isEmpty()) return false;
            }
            return true;
        })->values();
    }

    public function getUserId()
    {
        if ($this->request->routeIs('webapp.*'))
            return auth('web')->id();

        return auth()->check() ? auth()->user()->id : $this->request->user_id;
    }
}
