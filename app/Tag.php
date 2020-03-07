<?php

namespace App;

use App\Traits\HasLimit;

class Tag extends PianoLit
{
    use HasLimit;
    
    protected $labels = [
        'search' => ['mood', 'technique', 'genre', 'ranking'],
        'core' => ['level', 'sublevel', 'period', 'length']
    ];

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

    public function getBackground()
    {
        return asset('images/temp/'.strtolower($this->name).'.jpg');
    }
}
