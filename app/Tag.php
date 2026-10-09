<?php

namespace App;

use App\Traits\HasLimit;

class Tag extends PianoLit
{
    use HasLimit;
    
    protected $labels = [
        'search' => ['mood', 'technique', 'genre', 'ranking', 'season'],
        'core' => ['level', 'sublevel', 'period', 'length']
    ];

    protected $appends = ['cover_image', 'source'];

    private $specialTags = ['dreamy', 'elegant', 'flashy', 'crazy', 'melancholic', 'happy'];

    protected static function boot()
    {
        parent::boot();

        self::updated(function($tag) {
            $tag->pieces()->searchable();
        });

        self::deleting(function($tag) {
            $tag->pieces()->detach();
        });
    }

    public function getSourceAttribute()
    {
        return route('api.search');
    }

    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
        return $this->belongsToMany(Piece::class);
    }

    public function scopeName($query, $search)
    {
        return $query->where('name', $search);
    }

    public function scopeLabels($query)
    {
        return $this->labels;
    }

    public function scopeSpecial($query)
    {
        return $query->whereIn('name', $this->specialTags);
    }
    
    public function scopeLevels($query)
    {
    	return $query->where('type', 'level');
    }
    
    public function scopeExtendedLevels($query)
    {
        return $query->whereIn('type', ['level', 'sublevel'])->whereNotIn('name', ['beginner', 'intermediate'])->orderBy('order');
    }
    
    public function scopeMood($query)
    {
        return $query->where('type', 'mood');
    }

    public function scopeMostPopular($query)
    {
        return $query->withCount('pieces')->get()->sortByDesc('pieces_count')->values();
    }

    public function scopeLengths($query)
    {
        return $query->where('type', 'length');
    }

    public function scopePeriods($query)
    {
        return $query->where('type', 'period');
    }

    public function scopeTechnique($query)
    {
        return $query->where('type', 'technique');
    }

    public function scopeGenre($query)
    {
        return $query->where('type', 'genre');
    }

    public function scopeRanking($query, $ranking = null)
    {
        return $query->where('type', 'ranking')->where('name', 'like', "%$ranking%");
    }

    public function scopeByTypes($query, $except = [])
    {
        return $query->except('type', $except)->orderByMany(['type', 'name'])->get()->groupBy('type');
    }

    public function scopeDisplay($query)
    {
        $tags = $query->whereNotIn('type', ['ranking', 'length', 'level', 'sublevel'])
                      ->whereNotIn('name', ['beginner', 'intermediate'])
                      ->orderBy('name')
                      ->get();

        $levels = Tag::whereIn('type', ['level', 'sublevel'])
                      ->whereNotIn('name', ['beginner', 'intermediate'])
                      ->orderBy('order')
                      ->get();
        
        $levels->where('type', 'sublevel')->transform(function($item, $key) {
            $item->type = 'level';
        });

        $tags = $levels->merge($tags);

        return $tags;
    }

    public function scopeImprove($query)
    {
        return $query->whereIn('name', ['scales', 'arpeggios', 'octaves', 'thirds', 'fifths', 'fourths', 'right hand', 'left hand']);
    }

    public function scopeFamous($query)
    {
        return $query->where('name', 'famous');
    }

    public function getCoverImageAttribute()
    {
        return $this->type == 'period' ? asset('images/backgrounds/periods/'.strtolower($this->name).'.jpg') : null;
    }

    /** Web artwork only; keep the appended mobile cover_image unchanged. */
    public function getWebCoverImageAttribute()
    {
        return $this->webCoverImageExcept();
    }

    public function webCoverImageExcept(array $excluded = [])
    {
        $folder = strtolower((string) $this->name);
        if (!in_array($this->type, ['period', 'genre'], true) || !preg_match('/^[a-z0-9_-]+$/D', $folder)) {
            return in_array($this->cover_image, $excluded, true) ? null : $this->cover_image;
        }

        $path = 'images/backgrounds/periods/'.$folder;
        $images = array_values(array_filter(glob(public_path($path.'/*')) ?: [], function ($file) {
            return is_file($file) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'], true);
        }));

        $urls = $images ? array_map(function ($file) use ($path) {
            return asset($path.'/'.rawurlencode(basename($file)));
        }, $images) : [$this->cover_image];
        $available = array_values(array_diff(array_filter($urls), $excluded));
        return $available ? $available[array_rand($available)] : null;
    }
}
