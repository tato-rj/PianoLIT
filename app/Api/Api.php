<?php

namespace App\Api;

use App\Piece;

class Api extends Factory
{
	public function discover()
	{
        $key = \Redis::get('app.discover');

        $collection = \Cache::remember($key, days(1), function() {
            return collect([
                $this->order(0)->free('Free weekly pick'),
                $this->order(1)->composers('Composers'),
                $this->order(2)->latest('Latest pieces'),
                $this->order(4)->women('From women composers'),
                $this->order(5)->tag('Pieces that are'),
                $this->order(6)->levels('Levels'),
                $this->order(7)->similar('Like today\'s free pick', Piece::free()->first()),
                $this->order(8)->improve('Improve your'),
                $this->order(9)->for('Great for'),
                $this->order(10)->ranking('rcm', 'Equivalent to the RCM levels'),
                $this->order(11)->ranking('abrsm', 'Equivalent to the ABRSM levels'),
            ]);
        });
        
        $collection->splice(3, 0, [$this->order(3)->suggestions('For you')]);

        return $collection;
	}

	public function search($request)
	{
        if (! $request->has('search') || $request->search == '')
            return [];
        
		$options = $request->has('lazy-load') ? ['hitsPerPage' => 10, 'page' => $request->page ?? 0] : [];

        if ($model = $request->model) {
            $query = (new $model)->name($request->search)->first()->pieces();
        } else {
            $query = Piece::search($request->search);
        }

        if ($request->has('count'))
            return response()->json(['count' => $query->count()]);

        $results = $request->has('lazy-load') ? $query->paginate($options['hitsPerPage']) : $query->get();

        return $results->load(['tags', 'composer', 'favorites'])->each->isFavorited($request->user_id);            
	}
}
