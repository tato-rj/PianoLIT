<?php

namespace App\Services\Profile;

use App\{Subscription, User};
use App\Mail\ProfileEmailChange;
use Illuminate\Support\Facades\{Crypt, DB, Mail, URL};
use Illuminate\Validation\ValidationException;

class EmailChange
{
    public function send(User $user, $email)
    {
        $token = Crypt::encryptString(json_encode(['user' => $user->id, 'from' => $user->email, 'to' => $email,
            'stamp' => hash('sha256', $user->password)]));
        $url = URL::temporarySignedRoute('users.profile.email.verify', now()->addHour(), ['token' => $token]);
        Mail::to($email)->send(new ProfileEmailChange($url));
    }

    public function pending(User $user, $token)
    {
        try { $data = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR); }
        catch (\Throwable $exception) { abort(403, 'This email confirmation link is invalid.'); }
        abort_unless(is_array($data) && ($data['user'] ?? null) === $user->id && ($data['from'] ?? null) === $user->email
            && hash_equals(hash('sha256', $user->password), $data['stamp'] ?? '')
            && is_string($data['to'] ?? null) && filter_var($data['to'], FILTER_VALIDATE_EMAIL), 403, 'This email confirmation link is no longer valid.');
        return $data['to'];
    }

    public function apply($userId, $token)
    {
        return DB::transaction(function () use ($userId, $token) {
            $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $email = $this->pending($user, $token);
            if (User::whereRaw('LOWER(email) = ?', [$email])->whereKeyNot($userId)->exists()) {
                throw ValidationException::withMessages(['email' => 'This email address is already in use.'])->errorBag('emailChange');
            }
            $subscribers = Subscription::whereRaw('LOWER(TRIM(email)) IN (?, ?)', [mb_strtolower(trim($user->email)), $email])
                ->orderBy('id')->lockForUpdate()->with('lists')->get();
            if ($primary = $subscribers->first()) {
                // Both addresses have been proved. Consolidate explicit list
                // choices, retaining subscriber IDs used by courses and email
                // delivery guards instead of cascading their history away.
                $listIds = $subscribers->flatMap(function ($subscriber) { return $subscriber->getRelation('lists')->pluck('id'); })->unique()->all();
                $primary->lists()->syncWithoutDetaching($listIds);
                foreach ($subscribers as $subscriber) {
                    $subscriber->update(['email' => $email]);
                    if ($subscriber->id !== $primary->id) $subscriber->lists()->detach();
                }
            }
            DB::table('password_resets')->where('email', $user->email)->delete();
            $user->forceFill(['email' => $email, 'email_verified_at' => now(), 'remember_token' => \Illuminate\Support\Str::random(60)])->save();
            return $user;
        }, 3);
    }
}
