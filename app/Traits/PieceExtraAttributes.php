<?php

namespace App\Traits;

trait PieceExtraAttributes
{
    public function getLevelNameAttribute()
    {
        return $this->level->name;
    }

    // public function getPeriodAttribute($period)
    // {
    //     return ucfirst($period);
    // }

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
        return $this->basename() . ' in ' . $this->key;
    }

    public function getMediumNameAttribute()
    {
        $mediumName = $this->basename();
        $mediumName .= $this->catalogue_name ? "{$this->catalogue}" : ' in ' . $this->key;
        $mediumName .= $this->nickname ? " \"{$this->nickname}\"" : '';
        
        return $mediumName;       
    }

    public function getLongNameAttribute()
    {
        $fullName = $this->basename();

        if (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental']))
            $fullName .= " in $this->key";
        
        if ($this->collection_name)
            $fullName .= " from $this->collection_name";

        $fullName .= $this->catalogue_name ? " {$this->catalogue}" : '';
        $fullName .= $this->nickname ? " \"{$this->nickname}\"" : '';
        
        return $fullName;
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

        $collection .= $this->catalogue_name ? " {$this->catalogue}" : '';

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