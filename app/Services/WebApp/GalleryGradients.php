<?php

namespace App\Services\WebApp;

class GalleryGradients
{
    // Existing gradient() colors, in Discover order. The final two keep longer
    // signed-in feeds distinct when personal galleries are inserted.
    const COLORS = ['yellow', 'orange', 'red', 'darkpink', 'purple', 'darkblue', 'lightblue', 'teal', 'green', 'blue', 'pink'];

    public static function at($index)
    {
        return self::COLORS[$index % count(self::COLORS)];
    }
}
