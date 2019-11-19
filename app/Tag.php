<?php

namespace App;

class Tag extends PianoLit
{
    protected $labels = [
        'search' => ['mood', 'technique', 'genre', 'ranking'],
        'core' => ['level', 'period', 'length']
    ];

    private $specialTags = ['dreamy', 'elegant', 'flashy', 'crazy', 'melancholic', 'happy'];
    
    public function creator()
    {
        return $this->belongsTo(Admin::class);
    }

    public function pieces()
    {
        return $this->belongsToMany(Piece::class);
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
        $tags['mood'] = $this->mood()->orderBy('name')->get();
        $tags['technique'] = $this->technique()->orderBy('name')->get();
        $tags['genre'] = $this->genre()->get();
        $tags['levels'] = $this->levels()->get();
        $tags['lengths'] = $this->lengths()->get();
        $tags['periods'] = $this->periods()->get();
        $tags['ranking'] = $this->ranking()->get();

        foreach ($except as $type) {
            unset($tags[$type]);
        }
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
}
