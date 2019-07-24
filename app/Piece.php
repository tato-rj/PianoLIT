<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use App\Traits\{PieceExtraAttributes, PieceStatus};

class Piece extends PianoLit
{
    use PieceExtraAttributes, PieceStatus;
    
    protected $with = ['composer', 'tags', 'views'];
    protected $withCount = ['views', 'tags'];
    protected $appends = ['long_name', 'medium_name', 'recordingsAvailable', 'is_public_domain', 'level_name', 'timeline_url', 'period_name'];

    public static function boot()
    {
        parent::boot();

        self::deleting(function($piece) {
            $piece->tags()->detach();
            $piece->deleteFiles();
        });
    }

    public function getTagsCountAttribute($count)
    {
        return $count - 3;
    }

    public function getMissingInfoAttribute()
    {
        return 'No information available';
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

    public function getTimelineUrlAttribute()
    {
        return route('api.pieces.timeline', $this->id);
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

    public function mood()
    {
        return $this->tags()->where('type', 'mood')->get();
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

    public function scopeByRecordingsAvailable($query)
    {
        return $query->whereHas('tags', function($q) {
            return $q->whereIn('name', ['elementary', 'beginner', 'intermediate']);
        })->get()->groupBy('recordingsAvailable')->each(function($group) {
            $group['count'] = $group->count();
        });
    }
    
    public function isFavorited($user_id)
    {
        $result = \DB::table('favorites')->where([
            'user_id' => $user_id,
            'piece_id' => $this->id
        ])->first();

        return $result ? true : false;
    }

    public function scopeSearch($query, $array, $request = null)
    {
        $results = $query->where(function($query) use ($array, $request) {

            foreach ($array as $tag) {
                $query->whereHas('tags', function($q) use ($tag) {
                       $q->where('name', 'like', "%$tag%"); 
                });

                if (is_null($request) || $request->has('global')) {
                    $query->orWhereHas('composer', function($q) use ($tag) {
                           $q->where('name', 'like', "%$tag%");
                    })->orWhere('nickname', 'like', "%$tag%")
                      ->orWhere('name', 'like', "%$tag%")
                      ->orWhere('collection_name', 'like', "%$tag%")
                      ->orWhere('catalogue_name', $tag)
                      ->orWhere('catalogue_number', $tag);
                }
            }
            
        });

        return $results;
    }

    public function scopeFamous($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'famous');
        })->get();
        
        return $results;
    }

    public function scopeFlashy($query)
    {
        $results = $query->whereHas('tags', function($q) {
            $q->where('name', 'flashy');
        })->get();

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
        return $this->$attribute ? 'text-success' : 'text-muted';
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
        return Piece::exceptThis()
                    ->where(['composer_id' => $this->composer_id, 'catalogue_name' => $this->catalogue_name, 'catalogue_number' => $this->catalogue_number])
                    ->orderByRaw('LENGTH(collection_number)')
                    ->orderByRaw('LENGTH(movement_number)')
                    ->get();
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
}
