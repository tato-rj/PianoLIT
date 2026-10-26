<?php

namespace App\Games\Duels;

use App\Games\BeatHeroSettings;
use App\Games\ChordDetectiveSettings;
use App\Games\ChordsLabSettings;
use App\Games\GameFactory;
use App\Games\IntervalsLabSettings;
use App\Games\KeysLabSettings;
use App\Games\MemoryWizardSettings;
use App\Games\NoteMatchSettings;
use App\Games\NoteNestSettings;
use App\Games\NotePythonSettings;
use App\Games\PitchDetectiveSettings;
use App\Games\ToneTrekSettings;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class GameRegistry
{
    // The registry identifies engines; settings validation stays in GameFactory.
    public const GAMES = [
        'intervals-lab' => IntervalsLabSettings::class,
        'chords-lab' => ChordsLabSettings::class,
        'pitch-detective' => PitchDetectiveSettings::class,
        'chord-detective' => ChordDetectiveSettings::class,
        'tone-trek' => ToneTrekSettings::class,
        'note-python' => NotePythonSettings::class,
        'keys-lab' => KeysLabSettings::class,
        'note-nest' => NoteNestSettings::class,
        'note-match' => NoteMatchSettings::class,
        'memory-wizard' => MemoryWizardSettings::class,
        'beat-hero' => BeatHeroSettings::class,
    ];

    public static function settings(string $game, array $input = []): GameFactory
    {
        if (! isset(self::GAMES[$game])) {
            throw ValidationException::withMessages(['game' => 'This activity does not support Duels.']);
        }

        return new (self::GAMES[$game])($input);
    }

    public static function snapshot(string $game, array $input): array
    {
        $factory = self::settings($game);
        $validated = Validator::make($input, $factory->duelRules())->validate();
        $settings = self::settings($game, $validated)->replayOptions();
        if ($settings['practiceMode'] ?? false) {
            throw ValidationException::withMessages(['settings.practiceMode' => 'Choose a finite number of rounds and turn off practice mode to start a Duel.']);
        }

        return $settings;
    }
}
