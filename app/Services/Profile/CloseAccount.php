<?php

namespace App\Services\Profile;

use App\{Subscription, User};
use App\Services\Admin\RemoveUser;
use Illuminate\Support\Facades\DB;

class CloseAccount
{
    public function close($id, $confirmation)
    {
        DB::transaction(function () use ($id, $confirmation) {
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            DB::table('closed_accounts')->insert([
                'user_id' => $user->id,
                'signed_up_at' => $user->created_at,
                'origin' => in_array($user->origin, ['web', 'webapp', 'ios', 'android'], true) ? $user->origin : null,
                'closed_at' => now(),
            ]);
            // Historical imports and anonymous signups may have duplicate
            // subscribers. None may reconnect old preferences to a new signup.
            foreach (Subscription::whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower(trim($user->email))])->orderBy('id')->lockForUpdate()->get() as $subscriber) {
                $subscriber->delete();
            }
            // Reuses billing checks and transactional cleanup of private data.
            // Purchase/payment/activity rows remain under the original account ID.
            app(RemoveUser::class)->remove($user->id, $confirmation);
            DB::table('password_resets')->where('email', $user->email)->delete();
        }, 3);
    }
}
