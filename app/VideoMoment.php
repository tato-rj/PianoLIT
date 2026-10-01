<?php

namespace App;

class VideoMoment extends PianoLit
{
    protected $casts = ['start_time' => 'float', 'end_time' => 'float', 'sort_order' => 'integer'];

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class);
    }

    // Accept seconds, M:SS and H:MM:SS (including decimal seconds).
    public static function parseTime($value)
    {
        if (!is_string($value) && !is_numeric($value)) return null;
        $value = trim((string) $value);
        if (preg_match('/^\d+(?:\.\d{1,3})?$/D', $value)) return (float) $value;
        if (!preg_match('/^\d+:[0-5]\d(?:\.[0-9]{1,3})?$/D', $value) &&
            !preg_match('/^\d+:[0-5]\d:[0-5]\d(?:\.[0-9]{1,3})?$/D', $value)) return null;

        $seconds = 0;
        foreach (explode(':', $value) as $part) $seconds = $seconds * 60 + (float) $part;
        return $seconds;
    }
}
