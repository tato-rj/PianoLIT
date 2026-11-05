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
                'pedagogical' => "This could be a lovely place to start if you're new to the piano and enjoy hearing your progress take shape in a piece of music. The appeal is discovering what you can already express.",
                'non-traditional' => "If you're new to the piano but curious about modern sounds, this could be your next discovery. It offers a chance to explore a different musical world while you're still building your first collection of pieces.",
                'flashy' => "A little flair can make learning your first pieces especially exciting, and this one may suit you if you like music that catches the ear. It's a choice to consider when you're eager to share your playing.",
                'relaxing' => "It might be, especially if you're just starting out and love calm, reflective music. Learning a piece with this mood can make time at the piano feel like a quiet moment to yourself.",
                'default' => "This is worth considering if you're new to the piano and eager to make a piece your own. There's a particular pleasure in hearing familiar notes come together as music you can play from beginning to end.",
            ],
            'early beginner' => [
                'pedagogical' => "You may enjoy this if you're an early beginner who likes having a new piece to look forward to as your skills grow. Learning it can give those small discoveries at the keyboard a satisfying musical home.",
                'non-traditional' => "For an early beginner with curious ears, this could be an appealing change of style. Its modern character brings variety to your growing repertoire, with the pleasure of getting to know sounds that may be new to you.",
                'flashy' => "This could be a fun next choice if you're an early beginner who enjoys music with a sense of display. Learning something you'd like to play for friends or family can give the experience an extra spark.",
                'relaxing' => "If gentle, reflective music is what draws you to the piano, this may be a good fit for your early beginner repertoire. Its quiet mood offers room to enjoy the sound and feeling of what you're playing.",
                'default' => "It could be a satisfying next step if you're an early beginner ready to add another piece to your collection. Part of the enjoyment is finding that music which once felt unfamiliar gradually starts to feel like your own.",
            ],
            'late beginner' => [
                'pedagogical' => "This may appeal to you if you're a late beginner who enjoys the sense of discovery that comes with a new musical challenge. It's a chance to hear your growing abilities become part of a performance you can feel proud of.",
                'non-traditional' => "A taste for modern sounds could make this a good match for your late beginner repertoire. Learning it offers the pleasure of exploring a different style and discovering more about the kinds of music you most enjoy playing.",
                'flashy' => "If you love the idea of sharing a piece with a little flair, this could earn a place in your late beginner repertoire. The attraction is as much the anticipation of performing it as the satisfaction of learning it.",
                'relaxing' => "This might be your next piece if you're a late beginner drawn to music with a calm, reflective atmosphere. It may suit the moments when you want to settle into a mood and enjoy expressing something personal at the piano.",
                'default' => "You might find this a rewarding choice as a late beginner who enjoys seeing how far your playing has come. A new piece can bring that progress into focus while giving you something fresh to return to at the keyboard.",
            ],
            'early intermediate' => [
                'pedagogical' => "As you move into intermediate repertoire, this could offer the kind of musical project that makes learning feel purposeful. It may appeal if you enjoy discovering new possibilities in your playing and hearing them become part of a complete piece.",
                'non-traditional' => "This is one to consider if you're an early intermediate pianist looking for a different musical flavor. Its modern language offers a change of scenery, with the enjoyment of discovering sounds and ideas beyond your usual repertoire.",
                'flashy' => "If a piece with flair is what gets you excited to sit down at the piano, this could suit your early intermediate level. It offers a reason to look forward to sharing your music with an audience.",
                'relaxing' => "It could be, especially if you're an early intermediate pianist drawn to calm, dreamy music. The appeal is spending time with a reflective mood and discovering how much personality you can bring to a quiet musical moment.",
                'default' => "You may find your next musical project here if you're an early intermediate pianist who enjoys making interpretive choices. Learning a piece can be especially satisfying when your own taste begins to shape how the music sounds.",
            ],
            'late intermediate' => [
                'pedagogical' => "This could hold your interest if you're a late intermediate pianist who finds satisfaction in a piece gradually coming together. The enjoyment lies in bringing your experience to something new and hearing an interpretation take shape over time.",
                'non-traditional' => "If you're ready for a change of musical scenery, this may be an inviting addition to your late intermediate repertoire. Its modern character offers a chance to follow your curiosity and discover another side of your musical taste.",
                'flashy' => "A love of performing could make this a strong contender for your next late intermediate piece. Its flair may appeal if you enjoy the anticipation of sharing music and the satisfaction of giving a performance your own personality.",
                'relaxing' => "For a late intermediate pianist who loves reflective music, this could be a welcome addition to the repertoire. Much of the pleasure lies in its calm atmosphere and the room that mood leaves for a personal connection with the music.",
                'default' => "This deserves a place on your shortlist if you're a late intermediate pianist who enjoys getting to know a piece in depth. The attraction is building a lasting musical connection as its details become familiar and your interpretation becomes your own.",
            ],
            'advanced' => [
                'pedagogical' => "You could enjoy making this your next project if you're an advanced pianist who takes pleasure in the craft of playing. It may suit a love of detailed musical work and the satisfaction of hearing an interpretation gradually come into focus.",
                'non-traditional' => "For an advanced pianist with an appetite for modern music, this could open an interesting new direction. The pleasure is in getting to know a different musical language and discovering how naturally, or unexpectedly, it connects with your own taste.",
                'flashy' => "This may be the one if you're an advanced pianist who enjoys the excitement of preparing a piece with flair. It could be especially appealing when you want to bring both your musical personality and a sense of occasion to a performance.",
                'relaxing' => "If the reflective side of piano music is what keeps you coming back, this could belong in your advanced repertoire. Its calm character may appeal to your enjoyment of subtle expression and the intimacy of playing something that feels personal.",
                'default' => "An interest in {$this->composer->last_name} could make this a rewarding next choice for your advanced repertoire. Learning it offers time to get closer to the composer's musical world and discover what you most enjoy bringing to that music yourself.",
            ]
        ];

        if (! in_array($this->extended_level_name, array_keys($sentences)))
            return "This could be your next discovery if you're exploring {$this->level_name} repertoire and enjoy getting to know new music from the inside. Learning a piece offers the pleasure of turning something you enjoy hearing into something you can make your own.";

        foreach ($types as $type => $valid) {
            if ($valid && array_key_exists($type, $sentences[$this->extended_level_name]))
                return $sentences[$this->extended_level_name][$type];            
        }

        return $sentences[$this->extended_level_name]['default'];        
    }
}
