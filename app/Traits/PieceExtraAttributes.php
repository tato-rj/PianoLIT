<?php

namespace App\Traits;

trait PieceExtraAttributes
{
    public function getLevelNameAttribute()
    {
        return $this->level->name;
    }

    public function getPeriodNameAttribute()
    {
        return ucfirst($this->period->name);
    }

    // public function getLevelAttribute($level)
    // {
    //     return ucfirst($level);
    // }

    public function basename()
    {
        $number = $this->movement_number ? "$this->movement_number. " : '';
        return $number . $this->name;
    }

    public function getShortNameAttribute()
    {
        $name = $this->basename();
        $key = (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal'])) ? ' in ' . $this->key : null;
        $name .= $this->catalogue_name ? " {$this->catalogue}" : $key;
        return $name;
    }

    public function getMediumNameAttribute()
    {
        $name = $this->short_name;
        $name .= $this->nickname ? " \"{$this->nickname}\"" : '';
        return $name;       
    }

    public function getLongNameAttribute()
    {
        $name = $this->basename();

        if (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal']))
            $name .= " in $this->key";
        
        if ($this->collection_name)
            $name .= " from $this->collection_name";

        $name .= $this->catalogue_name ? " {$this->catalogue}" : '';
        $name .= $this->nickname ? " \"{$this->nickname}\"" : '';
        $name .= $this->isTranscription() ? " (piano transcription)" : '';
        
        return $name;
    }

    public function getTimelineNameAttribute()
    {
        if ($this->nickname)
            return $this->nickname;

        if ($this->collection_name && $this->catalogue_name)
            return $this->collection_name  . ' ' . $this->catalogue_name . ' ' . $this->catalogue_number;

        return $this->name;
    }
    
    public function getYoutubeArrayAttribute()
    {
        return unserialize($this->youtube);
    }

    public function getYoutubeCountAttribute()
    {
        return $this->youtube_array ? count($this->youtube_array) : 0;
    }

    public function getItunesArrayAttribute()
    {
        return unserialize($this->itunes);
    }

    public function getItunesCountAttribute()
    {
        return $this->itunes_array ? count($this->itunes_array) : 0;
    }

    public function getCatalogueAttribute()
    {
        $catalogue = "$this->catalogue_name $this->catalogue_number";
        $catalogue .= $this->collection_number ? " No.{$this->collection_number}" : '';
        return $catalogue;
    }

    public function getCollectionAttribute()
    {
        $collection = '';

        if ($this->collection_name)
            $collection .= $this->collection_name;

        if ($this->catalogue_name)
            $collection .= " $this->catalogue_name $this->catalogue_number";

        return $collection;
    }

    public function getTipsArrayAttribute()
    {
        return unserialize($this->tips);
    }

    public function getTagsArrayAttribute()
    {
        return $this->tags->pluck('name');
    }
}