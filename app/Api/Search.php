<?php

namespace App\Api;

use App\Piece;

class Search
{
	protected $request, $options, $query;
	protected $lateFilter = false;

    public function __construct($request)
    {
        $this->request = $request;

        $this->options = $request->has('lazy-load') ? ['hitsPerPage' => 10, 'page' => $request->page ?? 0] : [];

        $this->request->is_empty = ! $request->has('search') || strlen($request->search) <= 2;

        return $this;
    }

	public function query()
	{
        if ($this->request->is_empty)
            return $this;

        if ($model = $this->request->model) {
            $this->query = (new $model)->name($this->request->search)->first()->pieces()->latest();
        } else {
            $this->query = Piece::search($this->request->search);
        }

        return $this;       
	}

	public function filtered()
	{
		if (! $this->request->filters)
			return $this;

		if ($this->query instanceof \Algolia\ScoutExtended\Builder) {
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
            return null;

        if ($this->request->has('count'))
            return response()->json(['count' => $this->query->count()]);

        $pieces = $this->options ? $this->query->paginate($this->options['hitsPerPage']) : $this->query->get();

        $pieces = $pieces->load(['tags', 'composer', 'favorites']);

        if ($this->lateFilter) {
        	foreach ($pieces as $index => $piece) {
        		$validPiece = false;

        		foreach ($this->request->filters as $list) {
	        		if (! $piece->tags_array->intersect(json_decode($list))->isEmpty()) {
	        			$validPiece = true;
	        			break;
	        		}
        		}

        		if (! $validPiece)
	        		$pieces->forget($index);
        	}
        }

        return $pieces->each->isFavorited($this->request->user_id);
    }
}
