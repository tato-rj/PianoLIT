<?php

namespace App;

use Laravel\Scout\Searchable;
use Illuminate\Database\Eloquent\Builder;
use App\Traits\{PieceExtraAttributes, PieceStatus, HasMedia};
use Illuminate\Http\Request;
use App\Tools\Cropper;

class Piece extends PianoLit
{
    use PieceExtraAttributes, PieceStatus, Searchable, HasMedia;
    
    protected $folder = 'pieces';
    protected $withCount = ['views', 'tags', 'favorites', 'tutorials'];
    protected $casts = ['is_free' => 'boolean', 'is_attributed_to' => 'boolean', 'show_on_tour' => 'boolean'];
    protected $dates = ['highlighted_at'];
    protected $with = ['composer'];
    protected $appends = [
        'short_name',
        'medium_name', 
        'long_name', 
        'catalogue',
        'collection',
        'recordingsAvailable', 
        'is_public_domain', 
        'extended_level_name', 
        'level_name', 
        'level_id', 
        'level_number',
        'timeline_url', 
        'period_name', 
        'rankings', 
        'videos_array', 
        'itunes_array',
        'score',
        'audio',
        'audio_rh',
        'audio_lh',
        'medium_name_with_composer',
        'is_new',
        'number_of_pages',
        'for_who',
        'has_siblings',
        'image_background',
        'source',
        'media'
    ];
    
    protected $report_by = 'medium_name_with_composer';

    public static function boot()
    {
        parent::boot();

        self::deleting(function($piece) {
            $piece->tags()->detach();
            $piece->favorites()->delete();
            $piece->deleteFiles();
        });
    }

    public function getSourceAttribute()
    {
        return route('api.pieces.find');
    }

    public function toSearchableArray()
    {
        $array = [
            'name' => $this->name,
            'long_name' => $this->long_name,
            'nickname' => $this->nickname,
            'catalogue_name' => str_replace('Op.', 'Opus', $this->catalogue_name),
            'catalogue_number' => (integer) $this->catalogue_number,
            'collection_name' => $this->collection_name,
            'collection_number' => (integer) $this->collection_number,
            'movement_number' => (integer) $this->movement_number,
            'key' => $this->key,
            'tags_array' => $this->tags_array,
            'composer_name' => $this->composer->name,
            'nationality' => $this->composer->nationality,
            'country' => $this->composer->country->name,
            'views_count' => $this->views_count,
            'gender' => $this->composer->gender,
            'ethnicity' => $this->composer->ethnicity,
        ];

        return $array;
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

    public function performances()
    {
        return $this->hasMany(Performance::class);
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

    public function tutorials()
    {
        return $this->hasMany(Tutorial::class);
    }

    public function hasTutorials(array $categories)
    {
        return $this->tutorials()->pluck('category')->intersect($categories)->count() == count($categories);
    }

    public function saveTutorials($videos)
    {        
        $this->syncTutorials($videos);

        if ($videos) {
            foreach ($videos as $video) {
                $array = [
                    'type' => $video['type'],
                    'category' => $video['category'],
                    'description' => $video['description'],
                    'filename' => $video['filename'],
                ];

                if (array_key_exists('id', $video)) {
                    $this->tutorials()->find($video['id'])->update($array);
                } else {
                    $this->tutorials()->create($array);
                }
            }
        }
    }

    public function syncTutorials($videos)
    {
        $ids = $videos ? array_column($videos, 'id') : [];

        $this->tutorials()->whereIn('id', $this->tutorials()->pluck('id')->diff(collect($ids)))->delete();
    }

    public function getSubLevelAttribute()
    {
        return $this->tags->where('type', 'sublevel')->first();
    }

    public function getLevelAttribute()
    {
        return $this->tags->where('type', 'level')->first();
    }

    public function getExtendedLevelAttribute()
    {
        return $this->sublevel ?? $this->level; 
    }

    public function getLengthAttribute()
    {
        return $this->tags->where('type', 'length')->first();
    }

    public function getPeriodAttribute()
    {
        return $this->tags->where('type', 'period')->first();
    }

    public function getRanking($type, $complete = true)
    {
        $ranking = $this->tags->filter(function($item) use ($type) {
            return false !== stristr($item->name, $type);
        })->first();

        if ($ranking)
            return lastword($ranking->name);

        if ($complete) {
            if ($type == 'rcm' && $this->level->name == 'elementary')
                return 'PREP';

            if ($type == 'rcm' && $this->level->name == 'advanced')
                return 'GRAD';
        }

        return null;
    }

    public function mood()
    {
        return $this->tags->where('type', 'mood');
    }

    public function technique()
    {
        return $this->tags->where('type', 'technique');
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

    public function getLevelNumberAttribute()
    {
        $levels = ['elementary' => 1, 'beginner' => 2, 'intermediate' => 3, 'advanced' => 4];

        return $levels[$this->level_name];
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

    public function scopeWithAudio($query)
    {
        return $query->whereNotNull('audio_path');
    }

    public function scopeTour($query)
    {
        return $query->where('show_on_tour', true);
    }

    public function scopeWithVideos($query)
    {
        return $query->has('tutorials');
    }

    public function scopeWithTutorials($query)
    {
        return $query->has('tutorials', '>', 2)->get();
    }

    public function scopebyRecordingsAvailable($query)
    {
        return $query->whereHas('tags', function($q) {
            $q->whereIn('name', ['elementary', 'beginner', 'intermediate']);
        })->get()->groupBy('recordingsAvailable')->each(function($group) {
            $group['count'] = $group->count();
        });
    }

    public function scopeByEthnicity($query, $ethnicity)
    {
        return $query->whereHas('composer', function($query) use ($ethnicity) {
            return $query->where('ethnicity', $ethnicity);
        });
    }

    public function scopeByGender($query, $gender)
    {
        return $query->whereHas('composer', function($query) use ($gender) {
            return $query->where('gender', $gender);
        });
    }

    public function scopeByLevel($query, $level)
    {
        return $query->whereHas('tags', function($query) use ($level) {
            return $query->where('name', $level);
        });
    }

    public function scopeByPeriod($query, $period)
    {
        return $query->whereHas('tags', function($query) use ($period) {
            return $query->where('name', $period);
        });
    }

    public function scopeNotFamous($query)
    {
        return $query->whereDoesntHave('tags', function($q) {
            $q->where('name', '=', 'famous');
        });   
    }

    public function scopeByWomen($query)
    {
        $name;
        $count = 0;

        return $query->whereHas('composer', function($q) {
            $q->where('gender', 'female');
        })->inRandomOrder()->take(12)->get();
    }

    public function isFavorited($user_id)
    {
        $status = ! $this->favorites->where('user_id', $user_id)->isEmpty();

        $this->is_favorited = $status;

        return $status;
    }

    public function scopeFamous($query)
    {
        return $query->whereHas('tags', function($q) {
            $q->where('name', 'famous');
        });
    }

    public function scopePedagogical($query)
    {
        return $query->whereHas('tags', function($q) {
            $q->where('name', 'pedagogical');
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

    public function getDescriptionAttribute($description)
    {
        return $description ?? 'We\'re updating our database, please check back in just a few days.';
    }

    public function hasDescription()
    {
        if ($this->description == 'We\'re updating our database, please check back in just a few days.')
            return false;

        return (bool) $this->description;
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

    public function siblingsExist()
    {
        if (! $this->collection_name && ! $this->catalogue_number)
            return false;

        if ($this->collection_name == 'The Well-Tempered Clavier Book 1')
            return true;

        return Piece::exceptThis()->with(['composer', 'tags'])
                      ->where([
                        'composer_id' => $this->composer_id, 
                        'collection_name' => $this->collection_name, 
                        'catalogue_name' => $this->catalogue_name, 
                        'catalogue_number' => $this->catalogue_number])                      
                      ->exists();
    }

    public function siblings()
    {
        foreach (['Notebook for Anna Magdalena Bach', 'The Well-Tempered Clavier Book 1'] as $collection) {
            if ($this->collection_name == $collection)
                return Piece::exceptThis()->with(['composer', 'tags'])->where(['collection_name' => $collection])
                    ->orderByRaw('cast(collection_number as unsigned)')
                    ->orderByRaw('cast(movement_number as unsigned)')
                    ->get();
        }

        $pieces = Piece::exceptThis()->with(['composer', 'tags'])
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

    public function similar($strict = true)
    {
        $mood = $this->mood()->pluck('id');

        $similar = Piece::exceptThis()->with(['tags', 'composer'])->whereHas('tags', function(Builder $query) use ($mood) {
            $query->whereIn('id', $mood);
        })->get();

        foreach ($similar as $key => $piece) {
            if (! in_array($piece->level->id, [$this->level->id - 1, $this->level->id, $this->level->id + 1]))
                $similar->forget($key);
        }

        if ($strict) {
            foreach ($similar as $key => $piece) {
                if ($piece->period->id != $this->period->id)
                    $similar->forget($key);
            }
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

    public function generateScoreName()
    {
        return 'pianolit-' . str_slug($this->long_name) . '-' . lastnchar(mt_rand(), 4);
    }

    public function saveScore($file)
    {
        if (\Storage::disk('public')->exists($this->score_path))
            \Storage::disk('public')->delete($this->score_path);

        $filename = $this->generateScoreName() . '.' . $file->extension();

        return $this->update(['score_path' => $file->storeAs('app/score', $filename, 'public')]);
    }

    public function deleteScore()
    {
        if (\Storage::disk('public')->exists($this->score_path))
            \Storage::disk('public')->delete($this->score_path);
        
        return $this->update(['score_path' => null]);
    }

    public function cover_image()
    {
        return $this->cover_path ? asset('storage/' . $this->cover_path) : null;
    }

    public function scopeFree($query, $bool = true)
    {
        return $query->where('is_free', $bool);
    }

    public function scopeFreepicks($query, $ordered = true)
    {
        $highlights = $query->whereNotNull('highlighted_at');

        return $ordered ? 
                    $highlights->orderBy('highlighted_at', 'DESC') :
                    $highlights;
    }

    public function scopeFiltered($query, $random = true)
    {
        if (! request()->filters)
            return $random ? $query->inRandomOrder() : $query;

        foreach (request()->filters as $list) {
            $query->whereHas('tags', function($q) use ($list) {
                return $q->whereIn('name', json_decode($list));
            }); 
        }

        return $query;
    }

    public function getBackground()
    {
        return storage($this->cover_path);
    }

    public function getImageBackgroundAttribute()
    {
        if ($this->cover_path)
            return storage($this->cover_path);

        return $this->period->cover_image;
    }

    public function scopeLocalSearch($query, $array, $request = null)
    {
        if (empty($array))
            return $query->take(0);
        
        $results = $query->where(function($query) use ($array, $request) {
            foreach ($array as $tag) {
                $suffix = is_numeric(lastchar($tag)) ? null : '%';
                $query->whereHas('tags', function($q) use ($tag, $suffix) {
                       $q->where('name', 'like', "%$tag$suffix"); 
                });
                if (is_null($request) || $request->has('global')) {
                    $query->orWhereHas('composer', function($q) use ($tag) {
                           $q->where('name', 'like', "%$tag%")
                             ->orWhere('gender', 'like', "%$tag%");
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

    public function scopeDatatable($query, $actions = null)
    {
        return datatable($query)->withDate()->withBlade([
            'info' => view('admin.pages.pieces.table.info'),
            'level' => view('admin.pages.pieces.table.level'),
            'name' => view('admin.pages.pieces.table.name'),
            'ranking' => view('admin.pages.pieces.table.ranking'),
            'tags' => view('admin.pages.pieces.table.tags'),
            'favorited' => view('admin.pages.pieces.table.favorited'),
            'actions' => $actions ?? view('admin.pages.pieces.table.actions'),
        ])->make();
    }
}
