<?php

namespace App\Traits;

trait PieceStatus
{
    public function hasScore()
    {
        return (bool) $this->score_path;
    }

    public function hasAudio()
    {
        return (bool) $this->audio_path;
    }

    public function hasTags()
    {
        return $this->tags()->exists();
    }

    public function hasITunes()
    {
    	return $this->itunes_count > 0;
    }

    public function hasYoutube()
    {
    	return $this->youtube_count > 0;
    }

    public function isComplete()
    {
    	return $this->hasScore() && $this->hasAudio() && $this->hasTags() && $this->hasITunes() && $this->hasYoutube();
    }
}
