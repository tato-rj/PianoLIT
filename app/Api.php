<?php

namespace App;

use Carbon\Carbon;
use App\{Piece, Composer, Tag, Api, Country, Playlist, User};
use App\Traits\{Backgrounds, Dictionary};

class Api
{
    use Backgrounds, Dictionary;

    protected $color;

    public function __construct()
    {
        $this->colors = [null, null, 'yellow', 'orange', 'red', 'darkpink', 'purple', 'darkblue', 'lightblue', 'teal', 'green'];
        $this->limit = mt_rand(16,24);
    }
    
    public function free($title)
    {
        $collection = [Piece::free()->first()];

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find'), 'withBackground' => true]);

        return $this->createPlaylist($collection, ['type' => 'piece', 'title' => $title]);
    }

    public function latest($title)
    {
        $collection = Piece::with(['composer'])->latest()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, ['type' => 'piece', 'title' => $title]);
    }

    public function suggestions($title)
    {
        $user = User::find(request('user_id'));

        $collection = $user ? $user->suggestions(10) : Piece::inRandomOrder()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, ['type' => 'piece', 'title' => $title]);
    }

    public function composers($title)
    {
        $collection = Composer::inRandomOrder()->atLeast(4)->withCount('pieces')->take($this->limit)->get();

        $this->withAttributes($collection, ['source' => route('api.search')]);

        return $this->createPlaylist($collection, ['type' => 'composer', 'title' => $title]);
    }

    public function improve($title)
    {
        $collection = Tag::inRandomOrder()->atLeast(5)->improve()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, ['source' => route('api.search')]);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => $title]);
    }

    public function women($title)
    {
        $collection = Piece::with('composer')->byWomen()->shuffle();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => $title, 
            'tag' => 'women composers', 
            'url' => route('search.index', ['global', 'search' => 'woman composers'])
        ]);
    }

    public function ranking($ranking, $title)
    {
        $collection = Tag::atLeast(5)->ranking($ranking)->select('name')->withCount('pieces')->get();
        // route('search.index', ['global', 'search' => $ranking])
        $this->withAttributes($collection, ['source' => \URL::to('/api/search')]);

        return $this->createPlaylist($collection, [
            'type' => 'collection', 
            'title' => $title 
        ]);
    }

    public function levels($title)
    {
        $collection = Tag::atLeast(5)->levels()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, ['source' => \URL::to('/api/search')]);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => $title]);
    }

    public function tag($title)
    {
        $tag = Tag::mood()->has('pieces', '>', 20)->inRandomOrder()->pluck('name')->first();
        $collection = Piece::with('composer')->for($tag)->inRandomOrder()->take($this->limit)->get();

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => $title . ' ' . $tag, 
            'tag' => $tag,
            'url' => route('search.index', ['global', 'search' => $tag])
        ]);
    }

    public function periods($title)
    {
        $collection = Tag::atLeast(5)->periods()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, ['source' => \URL::to('/api/search')]);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => $title]);
    }

    public function similar()
    {
        $piece = Piece::famous()->inRandomOrder()->first();
        $collection = $piece->similar()->take($this->limit);
        $name = $piece->nickname ?? $piece->simple_name;

        $this->withAttributes($collection, ['type' => 'piece', 'source' => route('api.pieces.find')]);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'If you like', 
            'tag' => $piece->composer->last_name . '\'s ' . $name,
            'url' => route('search.index', ['global', 'search' => $name])
        ]);
    }

    public function order($order)
    {
        $this->color = array_infinite($this->colors, $order);

        return $this;
    }

    public function createPlaylist($model, array $args)
    {
        foreach ($args as $key => $value) {
            $playlist[$key] = $args[$key];            
        }

        $playlist['content'] = $model;

        return $playlist;
    }

    public function withAttributes($collection, array $args)
    {
        foreach ($collection as $model) {
            if (get_class($model) == 'App\Piece') {
                $model->setAttribute('name', $model->medium_name);
                $subtitle = $model->composer->short_name;
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

	public function setCustomAttributes(Piece $piece, $user_id)
	{
        $piece->setAttribute('is_favorited', $piece->isFavorited($user_id));
	}

    public function prepare($request, $pieces, $inputArray)
    {
        // If the request came from the input...
        if ($request->has('global')) {
            // ...look into each result to see if it contains all the words on the input
            $pieces->each(function($piece, $key) use ($inputArray, $pieces) {
                // Selects only the relevant rows for the search
                $pieceArray = array_values($piece->only([
                    'name',
                    'nickname',
                    'catalogue_full_name',
                    'catalogue_number',
                    'collection_name',
                    'collection_number',
                    'key']));
                // Bring every word to lower case
                $pieceArray = array_map('mb_strtolower', $pieceArray);
                // Prepare the tags array
                $pieceTags = $piece->tags->pluck('name');
                // Merges piece relevant fields with tags and composer name
                $pieceArray = $pieceTags->merge($pieceArray);
                $pieceArray->push(mb_strtolower($piece->composer->name))
                           ->push(mb_strtolower($piece->composer->gender));

                $matchesCount = 0;

                foreach ($pieceArray as $pieceTag) {
                    foreach ($inputArray as $inputTag) {
                        if (is_numeric($inputTag)) {
                            if ($pieceTag == $inputTag)
                                $matchesCount++;
                        } else {
                            if (strpos(removeAccents($pieceTag), removeAccents($inputTag)) !== false)
                                $matchesCount++;
                        }
                    }
                }

                if ($matchesCount < count($inputArray))
                    $pieces->forget($key);
            });
        }
        
        $pieces->each(function($result) use ($request) {
            self::setCustomAttributes($result, $request->user_id);
        });
    }

    public function prepareInput($request)
    {
        if (! $request->search)
            return [];

        $inputString = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $request->search)));

        $inputArray = array_map('mb_strtolower', explode(' ', $inputString));

        foreach ($inputArray as $key => $tag) {
            $inputArray = $this->considerRankingTags($inputArray, $key, $tag);
            $inputArray = $this->ignoreWord($inputArray, $key, $tag);
            $inputArray = $this->substituteWord($inputArray, $key, $tag);
        }

        foreach ($inputArray as $key => $tag) {
            $inputArray = $this->fixException($inputArray, $key, $tag, ['left', 'right', 'crossing'], 'hand');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['repeated'], 'notes');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['alternating'], 'hands');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['broken', 'block'], 'chords');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['alberti'], 'bass');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['finger'], 'substitution');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['4', '8'], 'hands');
        }

        return $inputArray;  
    }

    public function fixException($array, $key, $tag, $exceptions, $append)
    {
        if (in_array($tag, $exceptions)) {
            unset($array[$key], $array[$key+1]);
            array_push($array, "{$tag} {$append}");
        }

        return $array;
    }
    
    public function substituteWord($array, $key, $word)
    {
        if (array_key_exists($word, $this->substitute)) {
            unset($array[$key]);
            array_push($array, $this->substitute[$word]);
        }

        return $array;   
    }

    public function ignoreWord($array, $key, $word)
    {
        if (in_array($word, $this->ignore))
            unset($array[$key]);

        return $array;   
    }

    public function considerRankingTags($array, $key, $tag)
    {
        if ($tag == 'abrsm' || $tag == 'rcm') {
            if (array_key_exists($key+1, $array)) {
                $newTag = $array[$key] . ' ' . $array[$key+1];
                unset($array[$key+1]);
            } else {
                $newTag = $array[$key];
            }
            unset($array[$key]);
            array_push($array, $newTag);
        }

        return $array;
    }
}
