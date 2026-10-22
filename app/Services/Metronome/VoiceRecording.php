<?php

namespace App\Services\Metronome;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VoiceRecording
{
    public const MAX_RECORDINGS = 20;

    public function forUser(User $user): array
    {
        return ['recordings' => DB::table('metronome_voice_recordings')->where('user_id', $user->id)
            ->orderBy('created_at')->orderBy('id')->get(['id', 'name', 'revision'])->map(function ($row) {
                return (array) $row;
            })->all(), 'limit' => self::MAX_RECORDINGS];
    }

    public function find(User $user, string $id): array
    {
        $row = $this->owned($user, $id)->first();
        abort_unless($row, 404);

        return ['id' => $row->id, 'name' => $row->name, 'revision' => $row->revision, 'clips' => json_decode($row->clips, true)];
    }

    public function save(User $user, string $name, array $clips): array
    {
        foreach ($clips as $index => $clip) $this->validateClip($clip, $index);

        return DB::transaction(function () use ($user, $name, $clips) {
            User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (DB::table('metronome_voice_recordings')->where('user_id', $user->id)->count() >= self::MAX_RECORDINGS) {
                throw ValidationException::withMessages(['name' => 'You can save up to 20 voices. Delete one to make room.']);
            }
            $id = (string) Str::uuid();
            $next = (string) Str::uuid();
            DB::table('metronome_voice_recordings')->insert([
                'id' => $id, 'user_id' => $user->id, 'name' => $name, 'revision' => $next,
                'clips' => json_encode(array_values($clips)), 'created_at' => now(), 'updated_at' => now(),
            ]);

            return ['id' => $id, 'name' => $name, 'revision' => $next, 'clips' => array_values($clips)];
        });
    }

    public function rename(User $user, string $id, string $revision, string $name): array
    {
        return DB::transaction(function () use ($user, $id, $revision, $name) {
            $this->lockRevision($user, $id, $revision);
            $next = (string) Str::uuid();
            $this->owned($user, $id)->update(['name' => $name, 'revision' => $next, 'updated_at' => now()]);

            return ['id' => $id, 'name' => $name, 'revision' => $next];
        });
    }

    public function delete(User $user, string $id, string $revision): void
    {
        DB::transaction(function () use ($user, $id, $revision) {
            $this->lockRevision($user, $id, $revision);
            $this->owned($user, $id)->delete();
            // Delete the upgrade copy too if this was the original singleton.
            DB::table('metronome_voices')->where('user_id', $user->id)->where('revision', $id)->delete();
        });
    }

    private function owned(User $user, string $id)
    {
        return DB::table('metronome_voice_recordings')->where('user_id', $user->id)->where('id', $id);
    }

    private function lockRevision(User $user, string $id, string $revision): void
    {
        User::whereKey($user->id)->lockForUpdate()->firstOrFail();
        $current = $this->owned($user, $id)->value('revision');
        abort_if($current === null, 404);
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
