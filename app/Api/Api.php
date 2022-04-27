<?php

namespace App\Api;

use App\{Piece, Composer, Tag, Tutorial};
use Illuminate\Support\Facades\Redis;
use App\Playlist;
use App\Blog\Post;

class Api extends Factory
{
    protected $results, $request, $query, $options, $for;

	public function discover()
	{
        $key = Redis::get('app.discover');

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

    public function playlists($group = null)
    {
        $key = Redis::get('app.playlists.order');

        \Cache::remember($key, days(3), function() {
            $ids = Playlist::whereNull('group')->inRandomOrder()->pluck('id');

            Playlist::sort($ids);

            return $ids;
        });

        return Playlist::byGroup($group)->with('pieces')->has('pieces', '>', 5)->sorted()->complete();
    }

    public function post()
    {
        // $key = Redis::get('app.blog-post');

        // $post = \Cache::remember($key, days(1), function() {
            return Post::published()->inRandomOrder()->first();
        // });

        // return $post;
    }

    public function composersList()
    {
        // $key = Redis::get('app.allcomposers');

        // $composers = \Cache::remember($key, days(1), function() {
            return Composer::atLeast(1)->with(['country', 'pieces'])->get()->sortBy('last_name')->values();
        // });

        // return $composers;
    }

    public function search($request)
    {
        return (new Search($request))->query();
    }

    public function explore()
    {
        $key = Redis::get('app.explore');

        $collection = \Cache::remember($key, days(1), function() {
            $categories = Tag::display()->groupBy('type')->forget(['period', 'level', 'season']);
            $levels = Tag::extendedLevels()->withCount('pieces')->get();
            $harmony = Tutorial::latestHarmonicAnalysis(4);
            $highlights = Piece::freePicks()->get();
            $post = $this->post();
            $periods = Tag::periods()->withCount('pieces')->get();
            $composer = $highlights->shift()->composer;
            
            return collect([
                ['label' => 'Categories', 'collection' => $categories, 'celltype' => 'category'], 
                ['label' => 'Highlights', 'collection' => $highlights->shuffle()->take(16), 'celltype' => 'highlight'],
                ['label' => 'Originals', 'collection' => $post, 'celltype' => 'original'],
                ['label' => 'Periods', 'collection' => $periods, 'celltype' => 'period'],
                ['label' => 'Levels', 'collection' => $levels, 'celltype' => 'level'],
                // ['label' => 'Synthesia releases', 'collection' => $synthesia, 'celltype' => 'synthesia'],
                ['label' => 'Composer of the week', 'collection' => $composer, 'celltype' => 'composer'],
                ['label' => 'Latest harmonic analysis', 'collection' => $harmony, 'celltype' => 'harmony'],
            ]);
        });

        if ($this->for == 'webapp') {
            $synthesia = Tutorial::synthesia(12);
            $collection->splice(5, 0, [['label' => 'Synthesia releases', 'collection' => $synthesia, 'celltype' => 'synthesia']]);
        }

        return $collection;
    }

    public function querySuggestions()
    {
        $queries = collect([
                'pieces for beginners',
                'pieces by women composers',
                'repertoire for my left hand',
                'bach little preludes',
                'intermediate pieces by chopin',
                'pieces by black composers',
                'advanced arpeggios',
                'scales for beginners',
                'baroque pieces',
                'repertoire by asian composers',
                'intermediate pieces',
                'florence price'
            ])->shuffle();

        return collect(['queries' => $queries]);
    }

    public function for($device)
    {
        $this->for = $device;

        return $this;
    }
}
