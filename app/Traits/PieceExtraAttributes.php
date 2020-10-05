<?php

namespace App\Traits;

trait PieceExtraAttributes
{
    public function getScoreCopyrightAttribute($value)
    {
        if ($this->id == 652)
            return 'Creative Commons';

        return 'Creative Commons';
    }

    public function getIsNewAttribute()
    {
        return $this->created_at->gte(now()->subDays(3));    
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

        if (strhas($name, 'Les Barricades Mistérieuses'))
            $name = 'Les Barricades Mistérieuses';

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
                'pedagogical' => "This piece will inspire you to stay motivated and keep on learning.",
                'non-traditional' => "Ideal for beginners who like original pieces with modern sounds and unique characters.",
                'flashy' => "If you are starting out and want to wow your audience, this piece is for you!",
                'relaxing' => "Great piece for beginners who like relaxing, dreamy pieces.",
                'default' => "If you are just starting out and developing your technique, this piece will be a great one for you!",
            ],
            'early beginner' => [
                'pedagogical' => "Ideal for beginners looking for an inspiring and rewarding experience.",
                'non-traditional' => "A modern piece with original sounds, ideal for beginners who like to explore different styles.",
                'flashy' => "If you are a beginner and want to wow your audience, you'll love this piece!",
                'relaxing' => "Ideal for beginners who like inspiring, relaxing and dreamy pieces.",
                'default' => "A great piece for beginners who are polishing their technique.",
            ],
            'late beginner' => [
                'pedagogical' => "Ideal for late beginner levels looking for a challenging and inspiring piece to play.",
                'non-traditional' => "A very nice piece for those who like original and creative styles.",
                'flashy' => "For beginners who are up for the challenge and want to wow their audience.",
                'relaxing' => "If you are a beginner and like relaxing, dreamy music, this piece will be a good challenge for you!",
                'default' => "An excellent piece for beginners who are polishing their technique.",
            ],
            'early intermediate' => [
                'pedagogical' => "This is an amazing piece that will keep you motivated and help you get into the intermediate level repertoire.",
                'non-traditional' => "Ideal for intermediate level pianists who like original and non-traditional pieces.",
                'flashy' => "For intermediate pianists looking for an extra challenge that will bring their technique up to the next level.",
                'relaxing' => "Ideal for intermediate pianists who like inspiring, relaxing and dreamy pieces.",
                'default' => "An excellent piece for intermediate level pianists who are working on their technique.",              
            ],
            'late intermediate' => [
                'pedagogical' => "This piece is not as difficult as it sounds and is ideal to develop your technique get you ready for more demanding repertoire.",
                'non-traditional' => "Ideal for intermediate pianists with a solid foundation that want to explore modern sounds and original styles.",
                'flashy' => "This piece will impress your audience and give you the motivation you need to move on to even harder repertoire.",
                'relaxing' => "If you are at an intermediate level and enjoy playing relaxing music, this piece will be a great challenge!",
                'default' => "This is a great piece for intermediate level pianists with a solid foundation and strong technique.",
            ],
            'advanced' => [
                'pedagogical' => "This piece is great for advanced level pianists looking for a rewarding experience from a piece that is not as difficult as it looks like.",
                'non-traditional' => "Ideal for advanced pianists looking for a unique experience and want to learn new styles and techniques.",
                'flashy' => "This piece will impress your audience and might well become your next favorite.",
                'relaxing' => "An incredible piece for advanced pianists looking for a relaxing and inspiring piece to play.",
                'default' => "Great piece for advanced pianists who want to expand their repertoire and learn more about the music of {$this->composer->last_name}.",
            ]
        ];

        if (! in_array($this->extended_level_name, array_keys($sentences)))
            return "A great piece for {$this->level_name} pianists who are polishing their technique and expanding their repertoire.";

        foreach ($types as $type => $valid) {
            if ($valid && array_key_exists($type, $sentences[$this->extended_level_name]))
                return $sentences[$this->extended_level_name][$type];            
        }

        return $sentences[$this->extended_level_name]['default'];        
    }
}