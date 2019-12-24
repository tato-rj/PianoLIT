<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\{PieceExtraAttributes, PieceStatus, Searchable};
use Illuminate\Http\Request;
use App\Tools\Cropper;

class Piece extends PianoLit
{
    use PieceExtraAttributes, PieceStatus, Searchable;
    
    protected $googleCloud = 'https://storage.googleapis.com/pianolit-app/videos/';
    protected $folder = 'pieces';
    protected $withCount = ['views', 'tags'];
    protected $appends = [
        'short_name',
        'medium_name', 
        'long_name', 
        'catalogue',
        'collection',
        'recordingsAvailable', 
        'is_public_domain', 
        'level_name', 
        'timeline_url', 
        'period_name', 
        'rankings', 
        'videos_array', 
        'itunes_array', 
        'score',
        'audio',
        'audio_rh',
        'audio_lh',
    ];
    protected $report_by = 'medium_name_with_composer';

    public static function boot()
    {
        parent::boot();

        self::deleting(function($piece) {
            $piece->tags()->detach();
            $piece->deleteFiles();
        });
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views()
    {
        return $this->hasMany(PieceView::class);
    }

    public function composer()
    {
        return $this->belongsTo(Composer::class);
    }

    public function country()
    {
        return $this->belongsToThrough(Country::class, Composer::class);
    }

    public function playlists()
    {
        return $this->belongsToMany(Playlist::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function tutorialRequests()
    {
        return $this->hasMany(TutorialRequest::class);
    }

    public function getLevelAttribute()
    {
        return $this->tags()->where('type', 'level')->first();
    }

    public function getLengthAttribute()
    {
        return $this->tags()->where('type', 'length')->first();
    }

    public function getPeriodAttribute()
    {
        return $this->tags()->where('type', 'period')->first();
    }

    public function getRanking($ranking)
    {
        $ranking = $this->tags()->where('name', 'like', "$ranking%")->first();

        return $ranking ? lastword($ranking->name) : null;   
    }

    public function mood()
    {
        return $this->tags()->where('type', 'mood')->get();
    }

    public function cloudUrlFor($name)
    {
        $url = null;

        foreach ($this->videos_array as $video) {
            if (strhas($video, $name)) {
                $url = $video;
                break;
            }   
        }

        return $url;
    }

    public function getTagsCountAttribute($count)
    {
        return $count - 3;
    }

    public function getMissingInfoAttribute()
    {
        return 'No information available';
    }

    public function getSingleAttribute()
    {
        return is_null($this->catalogue_number) && ($this->catalogue_name == 'Op. posth.' || is_null($this->collection_name));
    }

    public function getOriginalEventAttribute()
    {
        if ($this->composed_in)
            return ' was composed by ' . $this->composer->short_name . $this->composer->calculateAge($this->composed_in, 'composition') . '.';

        if ($this->published_in)
            return ' was first published ' . $this->composer->calculateAge($this->published_in, 'publication') . '.';

        return null;
    }

    public function getScoreEditorAttribute($editor)
    {
        return $editor ?? $this->missing_info;
    }

    public function getScorePublisherAttribute($publisher)
    {
        return $publisher ?? $this->missing_info;
    }

    public function getScoreCopyrightAttribute($copyright)
    {
        return $copyright ?? 'This work is protected by copyright';
    }

    public function getTimelineUrlAttribute()
    {
        return route('api.pieces.timeline', $this->id);
    }

    public function deleteFiles()
    {
        \Storage::disk('public')->delete([$this->audio_path, $this->audio_rh_path, $this->audio_lh_path, $this->score_path]);
    }

    public function getIsPublicDomainAttribute()
    {
        return $this->score_url ? false : true;
    }

    public function getRecordingsAvailableAttribute()
    {
        $count = 0;

        if ($this->audio_path) $count += 1;
        if ($this->audio_path_rh) $count += 1;
        if ($this->audio_path_lh) $count += 1;

        return $count;
    }

    public function scopebyRecordingsAvailable($query)
    {
        return $query->whereHas('tags', function($q) {
            $q->whereIn('name', ['elementary', 'beginner', 'intermediate']);
        })->get()->groupBy('recordingsAvailable')->each(function($group) {
            $group['count'] = $group->count();
        });
    }
    
    public function scopeByGender($query)
    {
        return $query->get()->groupBy(function($piece) {
            $piece->composer->gender;
        })->each(function($gender) {
            $gender['count'] = $gender->count();
        });
    }

    public function scopeByWomen($query)
    {
        $name;
        $count = 0;

        $collection = $query->whereHas('composer', function($q) {
            $q->where('gender', 'female');
        })->get()->groupBy('composer.name');

        $collection->each(function($composer, $key) use ($collection) {
            $collection[$key] = $composer->random($composer->count() > 5 ? 5 : $composer->count());
        });

        return $collection->flatten();
    }

    public function isFavorited($user_id)
    {
        return ! $this->favorites->where('user_id', $user_id)->isEmpty();
    }

    public function scopeFamous($query)
    {
        return $query->whereHas('tags', function($q) {
            $q->where('name', 'famous');
        });
    }

    public function scopeFlashy($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'flashy');
        });

        return $results;
    }

    public function scopeFor($query, $tag)
    {
        $results = $query->whereHas('tags', function($q) use ($tag) {
            $q->where('name', $tag);
        });

        return $results;
    }

    public function scopeSuggestedTips($query, $request)
    {
        $tagsArray = explode(',', $request->tags);

        return $query->whereHas('tags', function($query) use ($tagsArray) {
                    $query->whereIn('id', $tagsArray);
                })->pluck('tips');
    }

    public function lookup($attribute)
    {
        return $this->$attribute ? 'text-success' : 'text-muted opacity-4';
    }

    public function isTranscription()
    {
        return $this->tags_array->contains('transcription');
    }

    public function scopeFilters($query, $filters)
    {
        foreach ($filters as $filter) {
            if (request()->has($filter)) {
                if (request($filter) == 'missing') {
                    $query->whereNull($filter);
                } else {
                    $query->where($filter, request($filter));
                }
            }
        }

        return $query;
    }

    public function siblings()
    {
        foreach (['Notebook for Anna Magdalena Bach'] as $collection) {
            if ($this->collection_name == $collection)
                return Piece::exceptThis()->where(['collection_name' => $collection])
                    ->orderByRaw('cast(collection_number as unsigned)')
                    ->orderByRaw('cast(movement_number as unsigned)')
                    ->get();
        }

        $pieces = Piece::exceptThis()
                    ->where(['composer_id' => $this->composer_id, 'collection_name' => $this->collection_name, 'catalogue_name' => $this->catalogue_name, 'catalogue_number' => $this->catalogue_number])
                    ->orderByRaw('cast(collection_number as unsigned)')
                    ->orderByRaw('cast(movement_number as unsigned)')
                    ->get();

        $pieces->each(function($piece, $key) use ($pieces) {
            if ($piece->single)
                $pieces->forget($key);
        });

        return $pieces;
    }

    public function similar()
    {
        $mood = $this->mood()->pluck('id');

        $similar = Piece::exceptThis()->whereHas('tags', function(Builder $query) use ($mood) {
            $query->whereIn('id', $mood);
        })->get();

        foreach ($similar as $key => $piece) {
            if ($piece->level->id != $this->level->id || $piece->period->id != $this->period->id)
                $similar->forget($key);
        }

        return $similar;
    }

    public function uploadCoverImage(Request $request)
    {
        if ($request->hasFile('cover_image')) {
            if ($this->cover_path)
                \Storage::disk('public')->delete([$this->cover_path, $this->thumbnail_path]);
         
            $this->update([
                'cover_path' => (new Cropper($request, $crop = true))->make('cover_image')->saveTo("$this->folder/cover_images/$this->id/")->getPath()
            ]);
        }
    }

    public function cover_image()
    {
        return $this->cover_path ? asset('storage/' . $this->cover_path) : null;
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function getBackground()
    {
        return storage($this->cover_path);
    }
}
