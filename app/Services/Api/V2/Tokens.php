<?php

namespace App\Services\Api\V2;

use App\User;
use Illuminate\Support\Facades\DB;

class Tokens
{
    public function issue(User $user): array
    {
        $token = bin2hex(random_bytes(32));
        $expires = now()->addDays(30);
        DB::table('api_v2_tokens')->insert([
            'user_id' => $user->id, 'token_hash' => hash('sha256', $token),
            'password_fingerprint' => $this->fingerprint($user), 'expires_at' => $expires, 'created_at' => now(),
        ]);
        return ['access_token' => $token, 'token_type' => 'Bearer', 'expires_at' => $expires->toIso8601ZuluString()];
    }

    public function resolve(?string $token): ?User
    {
        if (!$token || !preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $record = DB::table('api_v2_tokens')->where('token_hash', hash('sha256', $token))->where('expires_at', '>', now())->first();
        if (!$record) return null;
        $user = User::find($record->user_id);
        return $user && hash_equals($record->password_fingerprint, $this->fingerprint($user)) ? $user : null;
    }

    public function revoke(string $token): void
    {
        DB::table('api_v2_tokens')->where('token_hash', hash('sha256', $token))->delete();
    }

    private function fingerprint(User $user): string
    {
        return hash_hmac('sha256', (string) $user->password, config('app.key'));
    }
}
