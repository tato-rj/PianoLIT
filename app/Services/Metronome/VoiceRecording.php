<?php

namespace App\Services\Metronome;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoiceRecording
{
    public function forUser(User $user): array
    {
        $row = DB::table('metronome_voices')->where('user_id', $user->id)->first();

        return $row ? ['revision' => $row->revision, 'clips' => json_decode($row->clips, true)]
            : ['revision' => null, 'clips' => []];
    }

    public function save(User $user, ?string $revision, array $clips): array
    {
        foreach ($clips as $index => $clip) $this->validateClip($clip, $index);

        return DB::transaction(function () use ($user, $revision, $clips) {
            $this->lockRevision($user, $revision);
            $next = (string) Str::uuid();
            DB::table('metronome_voices')->upsert([
                ['user_id' => $user->id, 'revision' => $next, 'clips' => json_encode(array_values($clips)),
                    'created_at' => now(), 'updated_at' => now()],
            ], ['user_id'], ['revision', 'clips', 'updated_at']);

            return ['revision' => $next, 'clips' => array_values($clips)];
        });
    }

    public function delete(User $user, string $revision): void
    {
        DB::transaction(function () use ($user, $revision) {
            $this->lockRevision($user, $revision);
            DB::table('metronome_voices')->where('user_id', $user->id)->delete();
        });
    }

    private function lockRevision(User $user, ?string $revision): void
    {
        User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $current = DB::table('metronome_voices')->where('user_id', $user->id)->value('revision');
        abort_if($current !== $revision, 409, 'Your recording changed in another tab. Reload it before saving.');
    }

    private function validateClip(string $encoded, int $index): void
    {
        $wav = base64_decode($encoded, true);
        $valid = $wav !== false && base64_encode($wav) === $encoded && strlen($wav) >= 2924 && strlen($wav) <= 96044;
        if ($valid) {
            // Accept only our canonical 24 kHz, mono, 16-bit PCM WAV format.
            // Checking the actual bytes bounds decoded duration and rejects
            // mislabeled uploads, compressed data and extra RIFF chunks.
            $header = unpack('a4riff/Vsize/a4wave/a4fmt/VfmtSize/vformat/vchannels/Vrate/VbyteRate/valign/vbits/a4data/VdataSize', substr($wav, 0, 44));
            $valid = $header['riff'] === 'RIFF' && $header['wave'] === 'WAVE' && $header['fmt'] === 'fmt '
                && $header['fmtSize'] === 16 && $header['format'] === 1 && $header['channels'] === 1
                && $header['rate'] === 24000 && $header['byteRate'] === 48000 && $header['align'] === 2
                && $header['bits'] === 16 && $header['data'] === 'data' && $header['dataSize'] % 2 === 0
                && $header['dataSize'] === strlen($wav) - 44 && $header['size'] === strlen($wav) - 8;
        }
        if (! $valid) {
            throw ValidationException::withMessages(['clips.'.$index => 'Please record this number again.']);
        }
    }
}
