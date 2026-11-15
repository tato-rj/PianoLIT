<?php

namespace App\Console\Commands;

use App\Services\Pieces\AudioDuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\ExecutableFinder;

class BackfillPieceAudioDurations extends Command
{
    protected $signature = 'pieces:backfill-audio-durations';
    protected $description = 'Measure missing durations for existing full piece recordings';

    public function handle(AudioDuration $audio): int
    {
        $ffprobe = config('match-tour.ffprobe', 'ffprobe');
        if (!(is_file($ffprobe) && is_executable($ffprobe)) && !(new ExecutableFinder)->find($ffprobe)) {
            $this->error('ffprobe is required to measure piece audio durations.');
            return 1;
        }

        $updated = $unreadable = 0;
        DB::table('pieces')->select('id', 'audio_path')
            ->whereNotNull('audio_path')->where('audio_path', '!=', '')
            ->whereNull('audio_duration_seconds')
            ->chunkById(100, function ($pieces) use ($audio, &$updated, &$unreadable) {
                foreach ($pieces as $piece) {
                    $seconds = $audio->seconds($piece->audio_path);
                    if ($seconds === null) {
                        $unreadable++;
                        $this->warn("Piece {$piece->id}: recording duration unavailable.");
                        continue;
                    }

                    // A replacement uploaded while probing has its own duration.
                    $updated += DB::table('pieces')->where('id', $piece->id)
                        ->where('audio_path', $piece->audio_path)->whereNull('audio_duration_seconds')
                        ->update(['audio_duration_seconds' => $seconds]);
                }
            });

        $this->info("Saved {$updated} durations. Unavailable recordings: {$unreadable}.");
        return $unreadable === 0 ? 0 : 1;
    }
}
