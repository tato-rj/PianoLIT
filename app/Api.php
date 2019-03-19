<?php

namespace App;

use Carbon\Carbon;
use App\{Piece, Composer, Tag, Api, Country, Playlist};
use App\Traits\Backgrounds;

class Api
{
    use Backgrounds;

    public function forUser($id)
    {
        $user = User::find($id);

        if (! $user)
            return [];

        $collection = $user->suggestions(12);
        $this->withAttributes($collection, [
            'type' => 'piece',
            'source' => \URL::to('/api/pieces/find'),
            'color' => 'blue']);

        return $this->createPlaylist($collection, ['title' => "For {$user->first_name}"]);
    }

    public function trending()
    {
        $collection = Piece::orderBy('views', 'DESC')->take(10)->get();
        $this->withAttributes($collection, [
            'type' => 'piece',
            'source' => \URL::to('/api/pieces/find'),
            'color' => 'green']);

        return $this->createPlaylist($collection, ['title' => 'Trending']);
    }

    public function latest()
    {
        $collection = Piece::latest()->take(15)->get();
        $this->withAttributes($collection, [
            'type' => 'piece',
            'source' => \URL::to('/api/pieces/find'),
            'color' => 'teal']);

        return $this->createPlaylist($collection, ['title' => 'Latest']);
    }

    public function composers()
    {
        $collection = Composer::select('name')->withCount('pieces')->get();
        $this->withAttributes($collection, [
            'type' => 'collection',
            'source' => \URL::to('/api/search'),
            'color' => 'lightblue']);

        return $this->createPlaylist($collection, ['title' => 'Composers']);
    }

    public function periods()
    {
        $collection = Tag::periods()->select('name')->withCount('pieces')->get();
        $this->withAttributes($collection, [
            'type' => 'collection',
            'source' => \URL::to('/api/search'),
            'color' => 'purple']);

        return $this->createPlaylist($collection, ['title' => 'Periods']);
    }

    public function improve()
    {
        $collection = Tag::improve()->select('name')->withCount('pieces')->get();
        $this->withAttributes($collection, [
            'type' => 'collection',
            'source' => \URL::to('/api/search'),
            'color' => 'pink']);

        return $this->createPlaylist($collection, ['title' => 'Improve your']);
    }

    public function levels()
    {
        $collection = Tag::levels()->select('name')->withCount('pieces')->get();

        $this->withAttributes($collection, [
            'type' => 'collection',
            'source' => \URL::to('/api/search'),
            'color' => 'yellow']);

        return $this->createPlaylist($collection, ['title' => 'Levels']);
    }

    public function famous()
    {
        $collection = Piece::famous()->take(10);
        $this->withAttributes($collection, [
            'type' => 'piece',
            'source' => \URL::to('/api/pieces/find'),
            'color' => 'orange']);

        return $this->createPlaylist($collection, ['title' => 'Most famous']);
    }

    public function flashy()
    {
        $collection = Piece::flashy()->take(10);
        $this->withAttributes($collection, [
            'type' => 'piece',
            'source' => \URL::to('/api/pieces/find'),
            'color' => 'red']);

        return $this->createPlaylist($collection, ['title' => 'Flashy']);
    }

    public function createPlaylist($model, array $args)
    {
        $playlist['title'] = $args['title'];
        $playlist['content'] = $model;

        return $playlist;
    }

    public function withAttributes($collection, array $args)
    {
        foreach ($collection as $model) {
            if (get_class($model) == 'App\Piece') {
                $model->setAttribute('name', $model->medium_name);
                $number = $model->views;
                $string = 'views';
            } else {
                $model->name = ucfirst($model->name);
                $number = $model->pieces_count;
                $string = 'pieces';
            }

            $background = empty($args['background']) ? null : asset("pianolit/images/backgrounds/{$args['background']}.png");

            $model->setAttribute('source', $args['source']);
            $model->setAttribute('type', $args['type']);
            $model->setAttribute('color', $args['color']);
            $model->setAttribute('background', $background);
            $model->setAttribute('special_attribute', $args['special_attribute'] ?? null);
            $model->setAttribute('count', $number.' '.$string);
        }
    }

	public function setCustomAttributes($model, $user_id)
	{
        $classname = get_class($model);

        if ($classname == 'App\Piece') {
            $model->setAttribute('tips_array', $model->tips);
            $model->setAttribute('youtube_array', $model->youtube);
            $model->setAttribute('level', $model->level_name);
            $model->setAttribute('period', $model->period() ? $model->period()->name : null);
            $model->setAttribute('itunes_array', $model->itunes);
            $model->setAttribute('catalogue', $model->catalogue);
            $model->setAttribute('collection', $model->collection);
            $model->setAttribute('tags_array', $model->tags_array);
            $model->setAttribute('short_name', $model->short_name);
            $model->setAttribute('medium_name', $model->medium_name);
            $model->setAttribute('long_name', $model->long_name);
            $model->setAttribute('audio', $model->file_path('audio_path'));
            $model->setAttribute('audio_rh', $model->file_path('audio_path_rh'));
            $model->setAttribute('audio_lh', $model->file_path('audio_path_lh'));
            $model->setAttribute('score', $model->file_path('score_path'));
            $model->setAttribute('is_favorited', $model->isFavorited($user_id));
            $model->composer->setAttribute('alive_on', $model->composer->alive_on);
            $model->composer->setAttribute('short_name', $model->composer->short_name);
            $model->composer->setAttribute('born_at', $model->composer->born_at);
            $model->composer->setAttribute('died_at', $model->composer->died_at);
        
        } else if ($classname == 'App\Composer') {
        
            $model->setAttribute('alive_on', $model->alive_on);
            $model->setAttribute('born_at', $model->born_at);
            $model->setAttribute('died_at', $model->died_at);
            $model->setAttribute('short_name', $model->short_name);
        
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

                // Merges piece relevant fields with tags and composer name
                $pieceArray = $pieceTags->merge($pieceArray);
                $pieceArray->push($pieceComposer);

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
        $inputString = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $request->search)));

        $inputArray = array_map('mb_strtolower', explode(' ', $inputString));

        foreach ($inputArray as $key => $tag) {

            $inputArray = $this->fixException($inputArray, $key, $tag, ['left', 'right', 'crossing'], 'hand');
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
}
