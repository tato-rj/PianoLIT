<?php
namespace App\Games;
class MicrophoneSettings
{
    public static function current(): array
    {
        return config('games.microphone');
    }
}
