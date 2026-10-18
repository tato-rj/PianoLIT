<?php

namespace App\Services\WebApp;

use App\User;
use Illuminate\Support\Facades\DB;

class MetronomePreferences
{
    public const DEFAULTS = ['tempo' => 60, 'beats' => 1, 'sound' => 'woodblock'];
    public const SOUNDS = ['woodblock', 'click', 'claves', 'hi-hat', 'voice', 'female-voice'];

    public function forUser(?User $user): array
    {
        if (! $user) return self::DEFAULTS;

        $row = DB::table('web_metronome_preferences')->where('user_id', $user->id)->first(['tempo', 'beats', 'sound']);

        return $row ? ['tempo' => (int) $row->tempo, 'beats' => (int) $row->beats, 'sound' => $row->sound] : self::DEFAULTS;
    }

    public function save(User $user, array $changes): array
    {
        // A unique owner and a partial upsert keep concurrent first saves atomic
        // without overwriting another tab's changes to a different setting.
        DB::table('web_metronome_preferences')->upsert([
            array_merge(self::DEFAULTS, $changes, ['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]),
        ], ['user_id'], array_merge(array_keys($changes), ['updated_at']));

        return $this->forUser($user);
    }
}
