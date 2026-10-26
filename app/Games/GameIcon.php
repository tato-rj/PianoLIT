<?php
namespace App\Games;

class GameIcon
{
    public static function render(string $icon): string
    {
        return trim(view('games.challenges.components.icon', compact('icon'))->render());
    }
}
