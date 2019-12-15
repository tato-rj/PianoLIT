<?php

namespace App\Traits;

trait PieceExtraAttributes
{
    public function getLevelNameAttribute()
    {
        if ($this->level)
            return $this->level->name;

        return null;
    }

    public function getRankingsAttribute()
    {
        $rcm = $this->getRanking('rcm');
        $abrsm = $this->getRanking('abrsm');

        if (! $rcm) {
            if ($this->level_name == 'advanced') {
                $rcmDefault = 'This piece is beyond all RCM levels';
            } elseif ($this->level_name == 'elementary') {
                $rcmDefault = 'This piece is easier than RCM level 1';                
            } else {
                $rcmDefault = 'No RCM ranking available yet';
            }
        }

        if (! $abrsm) {
            if ($this->level_name == 'advanced') {
                $abrsmDefault = 'This piece is beyond all ABRSM levels';
            } elseif ($this->level_name == 'elementary') {
                $abrsmDefault = 'This piece is easier than ABRSM level 1';                
            } else {
                $abrsmDefault = 'No ABRSM ranking available yet';
            }
        }

        return [
            'rcm' => $rcm ? "Equivalent to RCM level $rcm" : $rcmDefault, 
            'abrsm' => $abrsm ? "Equivalent to ABRSM level $abrsm" : $abrsmDefault
        ];
    }

    public function getCatalogueFullNameAttribute()
    {
        return $this->catalogue_name == 'Op.' ? 'Opus' : $this->catalogue_name;
    }

    public function getPeriodNameAttribute()
    {
        return ucfirst($this->period->name);
    }

    public function basename()
    {
        $number = $this->movement_number ? "$this->movement_number. " : '';

        return $number . $this->name;
    }

    public function getSimpleNameAttribute()
    {
        $name = $this->name;
        $key = (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal'])) ? ' in ' . $this->key : null;
        $name .= $this->catalogue_name || $this->collection_number ? " {$this->catalogue}" : $key;
        return rm_whitespaces($name);
    }

    public function getShortNameAttribute()
    {
        $name = $this->basename();

        $key = (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal'])) ? ' in ' . $this->key : null;
        $name .= $this->catalogue_name || $this->collection_number ? " {$this->catalogue}" : $key;

        return rm_whitespaces($name);
    }

    public function getMediumNameAttribute()
    {
        $name = $this->short_name;
        $name .= $this->nickname ? " \"{$this->nickname}\"" : '';
        return rm_whitespaces($name);       
    }

    public function getLongNameAttribute()
    {
        $name = $this->basename();

        if (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal']))
            $name .= " in $this->key";
        
        if ($this->collection_name)
            $name .= " from $this->collection_name";

        $name .= $this->catalogue_name || $this->collection_number ? " {$this->catalogue}" : '';
        $name .= $this->nickname ? " \"{$this->nickname}\"" : '';
        $name .= $this->isTranscription() ? " (piano transcription)" : '';
        
        return rm_whitespaces($name);
    }

    public function getMediumNameWithComposerAttribute()
    {
        return $this->medium_name . ' by ' . $this->composer->short_name;
    }

    public function getTimelineNameAttribute()
    {
        if ($this->nickname)
            return $this->nickname;

        if ($this->collection_name && $this->catalogue_name)
            return $this->collection_name  . ' ' . $this->catalogue_name . ' ' . $this->catalogue_number;

        return $this->name;
    }
    
    public function getVideosArrayAttribute()
    {
        $videos = unserialize($this->videos);

        if (! $videos)
            return null;
        
        foreach ($videos as $index => $video) {
            $videos[$index] = $this->googleCloud . str_slug($this->composer->name) . '/' . $video . '.mp4';
        }

        // $videos = ['test'];

        return $videos;
    }

    public function getVideosArrayRawAttribute()
    {
        return unserialize($this->videos);
    }

    public function getVideosCountAttribute()
    {
        return $this->videos_array ? count($this->videos_array) : 0;
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
        $space = strhas($this->catalogue_name, '.') ? '' : ' ';
        $catalogue = "$this->catalogue_name$space$this->catalogue_number";
        $catalogue .= $this->collection_number ? " No.{$this->collection_number}" : '';
        return $catalogue;
    }

    public function getCollectionAttribute()
    {
        $collection = '';

        if ($this->collection_name)
            $collection .= $this->collection_name;

        if ($this->movement_number) {
            $collection .= $this->catalogue_name ? " {$this->catalogue}" : '';
        } else {
            $collection .= $this->catalogue_name ? " $this->catalogue_name $this->catalogue_number" : '';            
        }

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