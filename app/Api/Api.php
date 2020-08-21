<?php

namespace App\Api;

use App\{Piece, Composer};
use App\Blog\Post;

class Api extends Factory
{
    protected $results, $request, $query, $options;

	public function discover()
	{
        $key = \Redis::get('app.discover');

        $collection = \Cache::remember($key, days(1), function() {
            return collect([
                $this->order(0)->free('Free weekly pick'),
                $this->order(1)->composers('Composers'),
                $this->order(2)->latest('Latest pieces'),
                // FOR YOU ROW GOES HERE
                $this->order(4)->tutorials('Pieces with tutorials'),
                $this->order(5)->women('From women composers'),
                $this->order(6)->tag('Pieces that are'),
                // $this->order(6)->levels('Levels'),
                $this->order(7)->similar('Like today\'s free pick', Piece::free()->first()),
                // $this->order(8)->improve('Improve your'),
                // $this->order(9)->for('Great for'),
                $this->order(8)->ranking('rcm', 'Equivalent to the RCM levels'),
                $this->order(9)->ranking('abrsm', 'Equivalent to the ABRSM levels'),
            ]);
        });
        
        $collection->splice(3, 0, [$this->order(3)->suggestions('For you')]);

        return $collection;
	}

    public function post()
    {
        $key = \Redis::get('app.post');

        $post = \Cache::remember($key, days(1), function() {
            return Post::published()->inRandomOrder()->first();
        });

        return $post;
    }

    public function composersList()
    {
        $key = \Redis::get('app.composersList');

        $composers = \Cache::remember($key, days(1), function() {
            return Composer::atLeast(1)->get()->sortBy('last_name');
        });

        return $composers;
    }

    public function search($request)
    {
        return (new Search($request))->query();
    }
}
