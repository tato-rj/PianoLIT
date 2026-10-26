<?php

namespace App\Auth\ApiV2;

use App\Api\V2\Problem;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};

class Sessions
{
    private const DUMMY_PASSWORD_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function login(string $email, string $password): array
    {
        $email = mb_strtolower(trim($email));
        $matches = User::query()->setEagerLoads([])->withCount([])
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])->limit(2)->get();
        $verified = $matches->count() === 1 ? $matches->first() : null;
        $hash = $verified ? $verified->getAuthPassword() : self::DUMMY_PASSWORD_HASH;
        $valid = Hash::check($password, $hash);
        if (!$verified || !$valid) $this->invalidLogin();

        return DB::transaction(function () use ($verified, $hash, $email) {
            $user = User::query()->setEagerLoads([])->withCount([])->whereKey($verified->id)->lockForUpdate()->first();
            // A concurrent reset, email change or closure cannot issue a session
            // using credentials that were verified before that change.
            if (!$user || !hash_equals($hash, $user->getAuthPassword()) || mb_strtolower(trim($user->email)) !== $email) $this->invalidLogin();
            $token = 'plv2_'.bin2hex(random_bytes(32));
            $expires = now('UTC')->addDays(max(1, min(90, (int) config('api-v2.token_lifetime_days', 30))));
            DB::table('api_v2_sessions')->insert([
                'user_id' => $user->id, 'token_hash' => hash('sha256', $token),
                'credential_fingerprint' => $this->fingerprint($user),
                'expires_at' => $expires->toDateTimeString(), 'created_at' => now('UTC')->toDateTimeString(),
            ]);
            return ['token_type' => 'Bearer', 'access_token' => $token, 'expires_at' => $expires->format('Y-m-d\TH:i:s\Z'), 'account' => $this->account($user)];
        }, 3);
    }

    public function authenticate(Request $request): ?User
    {
        $request->attributes->remove('api.v2.session_id');
        $header = $request->headers->get('Authorization', '');
        if (strlen($header) > 128 || !preg_match('/\A(?i:Bearer) +(plv2_[a-f0-9]{64})\z/', $header, $match)) return null;
        $session = DB::table('api_v2_sessions')->where('token_hash', hash('sha256', $match[1]))
            ->where('expires_at', '>', now('UTC')->toDateTimeString())->first();
        if (!$session) return null;
        $user = User::query()->setEagerLoads([])->withCount([])->find($session->user_id);
        if (!$user || !hash_equals($session->credential_fingerprint, $this->fingerprint($user))) return null;
        $request->attributes->set('api.v2.session_id', $session->id);
        return $user;
    }

    public function logout(Request $request): void
    {
        DB::table('api_v2_sessions')->where('id', $request->attributes->get('api.v2.session_id'))
            ->where('user_id', $request->user('api_v2')->id)->delete();
    }

    public function account(User $user): array
    {
        return ['id' => (string) $user->id, 'first_name' => $user->first_name, 'last_name' => $user->last_name, 'email' => $user->email];
    }

    private function fingerprint(User $user): string
    {
        $key = (string) config('app.key');
        if ($key === '') throw new \LogicException('A configured application key is required.');
        return hash_hmac('sha256', $user->getAuthPassword()."\0".mb_strtolower(trim($user->email)), $key);
    }

    private function invalidLogin(): void
    {
        throw new Problem(401, 'invalid_credentials', 'The email or password is incorrect.');
    }
}
