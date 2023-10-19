<?php

namespace App\Api;

use App\{Piece, User, Tag, Composer};

abstract class Factory
{
	protected $colors = [null, null, 'yellow', 'orange', 'red', 'darkpink', 'purple', 'darkblue', 'lightblue', 'teal', 'green', 'yellow', 'orange'];

    public function __construct()
    {
        $this->limit = mt_rand(8,12);
    }

    public function free($title)
    {
        $collection = [Piece::free()->first()];

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find'), 'withBackground' => true]);

        return $this->createPlaylist($collection, ['row' => 'freepick', 'type' => 'piece', 'title' => $title]);
    }

    public function composers($title)
    {
        $collection = Composer::nonPedagogical()->with(['country', 'pieces'])->inRandomOrder()->atLeast(2)->withCount('pieces')->take($this->limit)->get();

        $blackComposers = Composer::nonPedagogical()->with(['country', 'pieces'])->byEthnicity('black')->inRandomOrder()->atLeast(1)->withCount('pieces')->take(1)->get();

        foreach ($blackComposers as $composer) {
            if (! $collection->contains($composer))
                $collection->push($composer);
        }

        $this->withAttributes($collection, ['source' => route('api.search')]);

        return $this->createPlaylist($collection->shuffle(), ['row' => 'composers', 'type' => 'composer', 'title' => $title]);
    }

    public function latest($title)
    {
        $collection = Piece::with(['composer'])->withVideos()->latest()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, ['row' => 'gallery', 'type' => 'piece', 'title' => $title]);
    }

    public function suggestions($title)
    {
        $user = User::find(request('user_id'));

        $collection = $user ? $user->suggestions(20)->shuffle()->take(10) : Piece::inRandomOrder()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, ['row' => 'gallery', 'type' => 'piece', 'title' => $title]);
    }

    public function tutorials($title)
    {
        $collection = Piece::with(['composer'])->withTutorials()->shuffle()->take($this->limit);

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, ['row' => 'gallery', 'type' => 'piece', 'title' => $title]);
    }

    public function women($title)
    {
        $collection = Piece::with('composer')->byWomen()->shuffle();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'piece', 
            'title' => $title, 
            'tag' => 'women composers', 
            'url' => route('search.index', ['global', 'search' => 'woman composers'])
        ]);
    }

    public function ethnicity($ethnicity, $title)
    {
        $collection = Piece::with('composer')->byEthnicity($ethnicity)->inRandomOrder()->take(12)->get()->shuffle();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'piece', 
            'title' => $title, 
            'tag' => $ethnicity.' composers', 
            'url' => route('search.index', ['global', 'search' => $ethnicity.' composers'])
        ]);
    }

    public function tag($title)
    {
        $tag = Tag::mood()->has('pieces', '>', 20)->inRandomOrder()->pluck('name')->first();
        $collection = Piece::with(['tags', 'composer'])->for($tag)->inRandomOrder()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'piece', 
            'title' => $title . ' ' . $tag, 
            'tag' => $tag,
            'url' => route('search.index', ['global', 'search' => $tag])
        ]);
    }

    public function levels($title)
    {
        $collection = Tag::extendedLevels()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, ['source' => \URL::to('/api/search')]);

        return $this->createPlaylist($collection, ['row' => 'gallery', 'type' => 'collection', 'title' => $title]);
    }

    public function similar($title, $piece)
    {
        $collection = $piece->similar()->take($this->limit);
        $name = $piece->nickname ?? $piece->simple_name;

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'piece', 
            'title' => $title,
            'tag' => $piece->composer->last_name . '\'s ' . $name,
            'url' => route('search.index', ['global', 'search' => $name])
        ]);
    }

    public function improve($title)
    {
        $collection = Tag::inRandomOrder()->atLeast(5)->improve()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, ['source' => route('api.search')]);

        return $this->createPlaylist($collection, ['row' => 'gallery', 'type' => 'collection', 'title' => $title]);
    }

    public function for($title)
    {
        $tag = Tag::extendedLevels()->whereNotIn('name', ['advanced'])->pluck('name')->shuffle()->first();

        $collection = Piece::with('composer')->for($tag)->inRandomOrder()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'piece', 
            'title' => $title . ' ' . $tag . ' levels', 
            'tag' => $tag,
            'url' => route('search.index', ['global', 'search' => $tag])
        ]);
    }

    public function ranking($ranking, $title)
    {
        $collection = Tag::atLeast(5)->ranking($ranking)->select('name')->withCount('pieces')->get();
        $this->withAttributes($collection, ['source' => \URL::to('/api/search')]);

        return $this->createPlaylist($collection, [
            'row' => 'gallery', 
            'type' => 'collection', 
            'title' => $title 
        ]);
    }

    //////////////////
    // BASE METHODS //
    //////////////////

    public function order($order)
    {
        $this->color = array_infinite($this->colors, $order);

        return $this;
    }

    public function withAttributes($collection, array $args)
    {
        foreach ($collection as $model) {
            if (get_class($model) == 'App\Piece') {
                $model->setAttribute('name', $model->medium_name);
                $subtitle = $model->attribution . $model->composer->short_name;
            } else {
                $model->name = ucfirst($model->name);
                $number = $model->pieces_count;
                $subtitle = $number.' '.'pieces';
            }

            $background = empty($args['withBackground']) ? null : $model->getBackground();

            $model->setAttribute('source', $args['source']);
            $model->setAttribute('type', $args['type'] ?? null);
            $model->setAttribute('color', $this->color);
            $model->setAttribute('background', $background);
            $model->setAttribute('special_attribute', $args['special_attribute'] ?? null);
            $model->setAttribute('subtitle', $subtitle);
        }
    }

    public function createPlaylist($model, array $args)
    {
        foreach ($args as $key => $value) {
            $playlist[$key] = $args[$key];            
        }

        $playlist['content'] = $model;

        return $playlist;
    }
}
