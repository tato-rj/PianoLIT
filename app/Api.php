<?php

namespace App;

use Carbon\Carbon;
use App\{Piece, Composer, Tag, Api, Country, Playlist};
use App\Traits\{Backgrounds, Dictionary};

class Api
{
    use Backgrounds, Dictionary;

    public function forUser($id)
    {
        $user = User::find($id);

        if (! $user)
            return [];

        $collection = $user->suggestions(12);
        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => 'blue']);

        return $this->createPlaylist($collection, ['type' => 'piece', 'title' => "For {$user->first_name}"]);
    }

    public function trending()
    {
        $collection = Piece::has('views')->get()->sortByDesc(function($piece) {
            return $piece->views_count;
        })->take(10);

        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => 'green']);

        return $this->createPlaylist($collection, ['type' => 'piece', 'title' => 'Trending']);
    }

    public function latest()
    {
        $collection = Piece::latest()->take(15)->get();

        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => 'teal']);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'Latest pieces',
            'url' => route('search.index', ['global', 'search'])
        ]);
    }

    public function composers()
    {
        $collection = Composer::has('pieces', '>', production() ? 15 : 2)->withCount('pieces')->get();
        $this->withAttributes($collection, [
            'source' => \URL::to('/api/search'),
            'color' => 'purple']);

        return $this->createPlaylist($collection, ['type' => 'composer', 'title' => 'Most famous composers']);
    }

    public function periods()
    {
        $collection = Tag::periods()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, [
            'source' => \URL::to('/api/search'),
            'color' => 'lightpink']);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => 'Periods']);
    }

    public function improve()
    {
        $collection = Tag::improve()->select('name')->withCount('pieces')->get();
        $this->withAttributes($collection, [
            'source' => \URL::to('/api/search'),
            'color' => 'yellow']);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => 'Improve your']);
    }

    public function levels()
    {
        $collection = Tag::levels()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, [
            'source' => \URL::to('/api/search'),
            'color' => 'orange']);

        return $this->createPlaylist($collection, ['type' => 'collection', 'title' => 'Levels']);
    }

    public function famous()
    {
        $collection = Piece::famous()->take(10);

        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => 'red']);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'Most famous',
            'url' => route('search.index', ['global', 'search' => 'famous'])
        ]);
    }

    public function flashy()
    {
        $collection = Piece::flashy()->take(10);

        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => 'blue']);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'Flashy',
            'url' => route('search.index', ['global', 'search' => 'flashy'])
        ]);
    }

    public function tag($title, $tag, $color = null)
    {
        $collection = Piece::for($tag)->inRandomOrder()->take(10);

        $this->withAttributes($collection, [ 
            'source' => route('api.pieces.find'),
            'color' => $color]);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => $title, 
            'tag' => $tag,
            'url' => route('search.index', ['global', 'search' => $tag])
        ]);
    }

    public function similar($color = null)
    {
        $piece = Piece::famous()->inRandomOrder()->first();
        $collection = $piece->similar()->take(10);
        $name = $piece->nickname ?? $piece->simple_name;

        $this->withAttributes($collection, [
            'source' => route('api.pieces.find'),
            'color' => $color]);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'If you like', 
            'tag' => $piece->composer->last_name . '\'s ' . $name,
            'url' => route('search.index', ['global', 'search' => $name])
        ]);
    }

    public function women()
    {
        $collection = Piece::byWomen()->inRandomOrder()->take(15)->get();

        $this->withAttributes($collection, [
            'source' => route('api.pieces.find'),
            'color' => 'teal']);

        return $this->createPlaylist($collection, [
            'type' => 'piece', 
            'title' => 'Pieces by', 
            'tag' => 'women composers', 
            'url' => route('search.index', ['global', 'search' => 'woman composers'])
        ]);
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
            // if (get_class($model) == 'App\Piece') {
            //     // $model->setAttribute('name', $model->medium_name);
            //     $subtitle = $model->composer->short_name;
            // } else {
            //     // $model->name = ucfirst($model->name);
            //     $number = $model->pieces_count;
            //     $subtitle = $number.' '.'pieces';
            // }

            $subtitle = get_class($model) == 'App\Piece' ? $model->composer->short_name : $model->pieces_count.' '.'pieces';
            $background = empty($args['background']) ? null : asset("pianolit/images/backgrounds/{$args['background']}.png");

            $model->setAttribute('source', $args['source']);
            // $model->setAttribute('type', $args['type']);
            $model->setAttribute('color', $args['color']);
            $model->setAttribute('background', $background);
            $model->setAttribute('special_attribute', $args['special_attribute'] ?? null);
            $model->setAttribute('subtitle', $subtitle);
        }
    }

	public function setCustomAttributes($model, $user_id)
	{
        $classname = get_class($model);

        if ($classname == 'App\Piece') {
            $model->setAttribute('videos_array', $model->videos);
            $model->setAttribute('level', $model->level_name);
            $model->setAttribute('period', $model->period ? $model->period->name : null);
            $model->setAttribute('itunes_array', $model->itunes);
            $model->setAttribute('catalogue', $model->catalogue);
            $model->setAttribute('collection', $model->collection);
            $model->setAttribute('tags_array', $model->tags_array);
            $model->setAttribute('short_name', $model->short_name);
            $model->setAttribute('medium_name', $model->medium_name);
            $model->setAttribute('long_name', $model->long_name);
            $model->setAttribute('audio', storage($model->audio_path));
            $model->setAttribute('audio_rh', storage($model->audio_path_rh));
            $model->setAttribute('audio_lh', storage($model->audio_path_lh));
            $model->setAttribute('score', storage($model->score_path));
            $model->setAttribute('is_favorited', $model->isFavorited($user_id));
            $model->composer->setAttribute('alive_on', $model->composer->alive_on);
            $model->composer->setAttribute('short_name', $model->composer->short_name);
            $model->composer->setAttribute('born_at', $model->composer->born_at);
            $model->composer->setAttribute('died_at', $model->composer->died_at);
            $model->composer->setAttribute('cover_image', storage($model->composer->cover_path));
        
        } else if ($classname == 'App\Composer') {
        
            $model->setAttribute('alive_on', $model->alive_on);
            $model->setAttribute('born_at', $model->born_at);
            $model->setAttribute('died_at', $model->died_at);
            $model->setAttribute('short_name', $model->short_name);
            $model->setAttribute('cover_image', storage($model->cover_path));
        
        }
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
                    'catalogue_name',
                    'catalogue_number',
                    'collection_name',
                    'collection_number',
                    'key']));

                // Bring every word to lower case
                $pieceArray = array_map('mb_strtolower', $pieceArray);
                // Prepare the tags array
                $pieceTags = $piece->tags()->pluck('name');
                // Prepares the composer name
                $pieceComposer = mb_strtolower($piece->composer()->pluck('name')->first());
                $composerGender = mb_strtolower($piece->composer()->pluck('gender')->first());
                // Merges piece relevant fields with tags and composer name
                $pieceArray = $pieceTags->merge($pieceArray);
                $pieceArray->push($pieceComposer)->push($composerGender);

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

            $inputArray = $this->fixException($inputArray, $key, $tag, ['left', 'right', 'crossing'], 'hand');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['broken', 'block'], 'chords');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['alberti'], 'bass');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['finger'], 'substitution');
            $inputArray = $this->fixException($inputArray, $key, $tag, ['4', '8'], 'hands');
            $inputArray = $this->ignoreWord($inputArray, $key, $tag);
            $inputArray = $this->substituteWord($inputArray, $key, $tag);

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
}
