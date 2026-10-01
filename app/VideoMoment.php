<?php

namespace App;

class VideoMoment extends PianoLit
{
    protected $casts = ['start_time' => 'float', 'end_time' => 'float', 'sort_order' => 'integer'];

    public function tutorial()
    {
        return $this->belongsTo(Tutorial::class);
    }

    public static function formatTimeInput($value)
    {
        if ($value === null || $value === '') return '';
        $seconds = static::parseTime($value);
        if ($seconds === null || $seconds > 9999999.999) return is_scalar($value) ? (string) $value : '';
        $milliseconds = (int) round($seconds * 1000);
        $fraction = $milliseconds % 1000;

        return str_pad((string) intdiv($milliseconds, 60000), 2, '0', STR_PAD_LEFT).':'.
            str_pad((string) (intdiv($milliseconds, 1000) % 60), 2, '0', STR_PAD_LEFT).
            ($fraction ? '.'.rtrim(str_pad((string) $fraction, 3, '0', STR_PAD_LEFT), '0') : '');
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
