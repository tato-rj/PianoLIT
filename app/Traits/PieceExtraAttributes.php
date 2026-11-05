<?php

namespace App\Traits;

trait PieceExtraAttributes
{
    public function getIsNewAttribute()
    {
        return $this->created_at->gte(now()->subDays(5));    
    }

    public function getAudioAttribute()
    {
        return storage($this->audio_path);
    }

    public function getAudioRhAttribute()
    {
        return storage($this->audio_path_rh);        
    }
    
    public function getAudioLhAttribute()
    {
        return storage($this->audio_path_lh);        
    }

    public function getAudioArrayAttribute()
    {
        return array_filter(['full' => $this->audio, 'rh' => $this->audio_rh, 'lh' => $this->audio_lh]);
    }

    public function getTagsListAttribute()
    {
        return $this->tags->implode('name', ',');
    }
    
    public function getScoreAttribute()
    {
        return storage($this->score_path);
    }

    public function getScoreFullPathAttribute()
    {
        return \Storage::disk('public')->path($this->score_path);
    }

    public function getLevelNameAttribute()
    {
        if ($this->level)
            return $this->level->name;

        return null;
    }

    public function getExtendedLevelNameAttribute()
    {
        if ($this->level)
            return $this->extendedLevel->name;

        return null;
    }

    public function getAttributionAttribute()
    {
        return $this->is_attributed_to ? 'attr. ' : null;
    }

    public function getLevelIdAttribute()
    {
        if ($this->level)
            return $this->level->id;

        return null;
    }

    public function getLevelOrderAttribute()
    {
        if (! $this->level)
            return null;

        if (strhas($this->extended_level_name, 'early')) {
            $diff = -0.25;
        } elseif (strhas($this->extended_level_name, 'late')) {
            $diff = 0.25;
        } else {
            $diff = 0;
        }

        return $this->level->id + $diff;
    }

    public function getRankingsAttribute()
    {
        $rcm = $this->getRanking('rcm', false);
        $abrsm = $this->getRanking('abrsm', false);
        $suzuki = $this->getRanking('suzuki', false);

        if (! $rcm) {
            if ($this->level_name == 'advanced') {
                $rcmDefault = 'Equivalent to RCM diploma level';
            } elseif ($this->level_name == 'elementary') {
                $rcmDefault = 'Equivalent to RCM preparatory levels';                
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

        if (! $suzuki) {
            $suzukiDefault = null;
        }

        return [
            'rcm' => $rcm ? "Equivalent to RCM level $rcm" : $rcmDefault, 
            'abrsm' => $abrsm ? "Equivalent to ABRSM level $abrsm" : $abrsmDefault,
            'suzuki' => $suzuki ? "Equivalent to Suzuki book $suzuki" : $suzukiDefault
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
        $number = $this->movement_number ? "$this->movement_number. " : '';

        if ($this->collection_name == 'Sonata') {
            $name = $this->name;
        } else {
            $name = $this->nickname ?? $this->name;
        }
        
        $name = $number . $name;

        $key = (! in_array($this->key, ['Modal', 'Serial', 'Chromatic', 'Experimental', 'Atonal'])) ? ' in ' . $this->key : null;
        $name .= $this->catalogue_name || $this->collection_number ? " {$this->catalogue}" : $key;

        if (strhas($name, 'Les Barricades Mistérieuses'))
            $name = 'Les Barricades Mistérieuses';

        return rm_whitespaces($name);
    }

    public function getMediumNameAttribute()
    {
        // $name = $this->short_name;

        // $name .= $this->nickname ? " \"{$this->nickname}\"" : '';

        // return rm_whitespaces($name);       
        return $this->short_name;
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

    public function getLongNameWithComposerAttribute()
    {
        return $this->long_name . ' by ' . $this->composer->short_name;
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
        return $this->tutorials->toArray();
    }

    public function getVideosArrayRawAttribute()
    {
        return unserialize($this->videos);
    }

    public function getVideosCountAttribute()
    {
        return 0;
    }

    public function getPerformanceUrlAttribute()
    {
        $tutorial = $this->tutorials()->where('type', 'Performance')->first();

        return $tutorial ? $tutorial->video_url : null;
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

    public function getHasSiblingsAttribute()
    {
        return $this->siblingsExist();
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

    public function getComposerGenderAttribute()
    {
        return $this->composer->gender;
    }

    public function getNumberOfPagesAttribute()
    {
        switch ($this->length->name) {
            case 'short':
                return '1-2 pages';
                break;

            case 'medium':
                return '3-4 pages';
                break;

            case 'long':
                return '5+ pages';
                break;

            default:
                return '1 page';
                break;
        }
    }

    public function getForWhoAttribute()
    {
        $types = [
            'non-traditional' => in_array($this->period->name, ['modern', 'contemporary']),
            'flashy' => $this->tags_array->contains('flashy'),
            'pedagogical' => $this->composer->is_pedagogical,
            'relaxing' => in_array('relaxing', $this->tags_array->toArray()) ||
                          in_array('dreamy', $this->tags_array->toArray()) ||
                          in_array('meditative', $this->tags_array->toArray())
        ];

        $sentences = [
            'elementary' => [
                'pedagogical' => "It could be just what you need to stay motivated and keep on learning.",
                'non-traditional' => "This could suit you if you're a beginner who likes original pieces with modern sounds and unique character.",
                'flashy' => "If you're starting out and want to wow your audience, this could be the piece for you.",
                'relaxing' => "You might enjoy this if you're a beginner who likes relaxing, dreamy pieces.",
                'default' => "This could be a good next step if you're just starting out and developing your technique.",
            ],
            'early beginner' => [
                'pedagogical' => "You might enjoy this if you're a beginner looking for an inspiring and rewarding experience.",
                'non-traditional' => "This could be your next piece if you're a beginner who likes exploring different styles and modern sounds.",
                'flashy' => "If you're a beginner and want to wow your audience, this could be one to try.",
                'relaxing' => "It might be a good fit if you're a beginner who likes inspiring, relaxing and dreamy pieces.",
                'default' => "This could be a rewarding next step if you're a beginner polishing your technique.",
            ],
            'late beginner' => [
                'pedagogical' => "You might enjoy this if you're a late beginner looking for a challenging and inspiring piece to play.",
                'non-traditional' => "This could be a good match if you like original and creative styles.",
                'flashy' => "If you're a beginner ready for a challenge and want to wow your audience, this could be your next piece.",
                'relaxing' => "It could be a rewarding challenge if you're a beginner who enjoys relaxing, dreamy music.",
                'default' => "This might be a good next choice if you're a beginner polishing your technique.",
            ],
            'early intermediate' => [
                'pedagogical' => "This could keep you motivated as you begin exploring intermediate repertoire.",
                'non-traditional' => "You might enjoy this if you're an intermediate pianist who likes original, non-traditional pieces.",
                'flashy' => "It could be your next challenge if you're an intermediate pianist looking to take your technique further.",
                'relaxing' => "This could be a great fit if you're an intermediate pianist who enjoys inspiring, relaxing and dreamy pieces.",
                'default' => "It might be a good next step if you're an intermediate pianist working on your technique.",
            ],
            'late intermediate' => [
                'pedagogical' => "This could be a good next step if you want to develop your technique and prepare for more demanding repertoire.",
                'non-traditional' => "This could suit you if you're an intermediate pianist with a solid foundation who wants to explore modern sounds and original styles.",
                'flashy' => "If you want to impress your audience and feel ready for harder repertoire, this could be your next challenge.",
                'relaxing' => "You might enjoy this challenge if you're an intermediate pianist who likes playing relaxing music.",
                'default' => "It could be a good match if you're an intermediate pianist with a solid foundation and strong technique.",
            ],
            'advanced' => [
                'pedagogical' => "This could be a rewarding choice if you're an advanced pianist looking for something less demanding than it appears.",
                'non-traditional' => "You might enjoy this if you're an advanced pianist looking for a unique experience with new styles and techniques.",
                'flashy' => "If you want to impress your audience, this might become your next favorite.",
                'relaxing' => "This could be a good fit if you're an advanced pianist looking for something relaxing and inspiring to play.",
                'default' => "You might enjoy this if you're an advanced pianist keen to expand your repertoire and explore the music of {$this->composer->last_name}.",
            ]
        ];

        if (! in_array($this->extended_level_name, array_keys($sentences)))
            return "This could be a good next step if you're a {$this->level_name} pianist polishing your technique and expanding your repertoire.";

        foreach ($types as $type => $valid) {
            if ($valid && array_key_exists($type, $sentences[$this->extended_level_name]))
                return $sentences[$this->extended_level_name][$type];            
        }

        return $sentences[$this->extended_level_name]['default'];        
    }
}
