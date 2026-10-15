<?php

namespace App\Http\Controllers\Admin;

use App\User;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\{DB, Schema};

class SuperUserAccessController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function update(User $user)
    {
        $trackingReady = Schema::hasTable('super_user_access_periods');
        $user = DB::transaction(function () use ($user, $trackingReady) {
            $account = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $enabled = ! $account->super_user;
            $account->update(['super_user' => $enabled]);
            if ($trackingReady) {
                $periods = DB::table('super_user_access_periods')->where('current_user_id', $account->id);
                if ($enabled) {
                    // A flag changed outside this admin route may leave an old open period.
                    // Close its association without inventing a past revocation date.
                    $periods->update(['current_user_id' => null]);
                    DB::table('super_user_access_periods')->insert([
                        'user_id' => $account->id, 'current_user_id' => $account->id,
                        'granted_at' => now(), 'granted_by' => auth('admin')->id(),
                        'first_observed_at' => now(),
                    ]);
                } else {
                    $closed = $periods->update(['current_user_id' => null, 'revoked_at' => now(), 'revoked_by' => auth('admin')->id()]);
                    if (! $closed) {
                        DB::table('super_user_access_periods')->insert([
                            'user_id' => $account->id, 'current_user_id' => null,
                            'first_observed_at' => now(), 'revoked_at' => now(), 'revoked_by' => auth('admin')->id(),
                        ]);
                    }
                }
            }
            return $account;
        });

        return response()->json(['status' => $user->fullName . '\'s super status has been updated.']);
    }
}
