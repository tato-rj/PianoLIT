<?php

namespace App\Services\Admin;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveUser
{
    public function remove($id, $confirmation, $purge = false)
    {
        DB::transaction(function () use ($id, $confirmation, $purge) {
            // Re-read and serialize changes to the route-bound account, not a submitted ID.
            $user = User::whereKey($id)->lockForUpdate()->firstOrFail();
            if (mb_strtolower(trim($confirmation)) !== mb_strtolower(trim($user->email))) {
                throw ValidationException::withMessages(['confirmation_email' => 'Enter this account’s email address to confirm.'])
                    ->errorBag($purge ? 'purgeAccount' : 'deleteAccount');
            }
            if ($reason = (new UserRemovalPolicy)->reason($user->id, true)) {
                throw ValidationException::withMessages(['account_removal' => $reason])->errorBag($purge ? 'purgeAccount' : 'deleteAccount');
            }
            if ($purge) {
                // A later Redis import or stale mobile request must not recreate this history.
                DB::table('activity_purged_users')->insertOrIgnore(['user_id' => $user->id, 'purged_at' => now()]);
                $this->activity($user->id);
            }

            // These older account-owned tables have no cascading foreign keys.
            // Delete by user, avoiding folder hooks that can traverse other owners.
            DB::table('favorite_folders')->where('user_id', $user->id)->delete();
            DB::table('synthesia_requests')->where('user_id', $user->id)->delete();
            // Existing model hooks remove newsletter/location/favorites/piece views.
            // Database cascades remove private markings, recent pieces and auth links.
            // Billing/payment records remain; deleting an account does not cancel billing.
            if (!$user->delete()) throw new \RuntimeException('Account deletion was not completed.');
        }, 3);
    }

    private function activity($userId)
    {
        $visits = DB::table('activity_visits')->where(function ($query) use ($userId) {
            $query->where('user_id', $userId)->orWhere(function ($anonymous) use ($userId) {
                // A verified anonymous link cannot authorize deleting another account's visit.
                $anonymous->whereNull('user_id')->whereIn('id', DB::table('activity_visit_links')->where('user_id', $userId)->select('visit_id'));
            });
        });
        while ($ids = (clone $visits)->orderBy('id')->limit(200)->lockForUpdate()->pluck('id')->all()) {
            DB::table('activity_events')->where(function ($query) use ($ids) {
                $query->whereIn('visit_id', $ids)->orWhereIn('page_id', DB::table('activity_pages')->whereIn('visit_id', $ids)->select('id'));
            })->delete();
            DB::table('activity_pages')->whereIn('visit_id', $ids)->delete();
            DB::table('activity_visit_links')->whereIn('visit_id', $ids)->delete();
            DB::table('activity_visits')->whereIn('id', $ids)->delete();
        }
        // Cover ungrouped/imported records and pages with no remaining parent visit.
        $pages = DB::table('activity_pages')->where('user_id', $userId);
        DB::table('activity_events')->where(function ($query) use ($userId, $pages) {
            $query->where('user_id', $userId)->orWhereIn('page_id', (clone $pages)->select('id'));
        })->delete();
        $pages->delete();
        DB::table('activity_visit_links')->where('user_id', $userId)->delete();
        DB::table('logs')->where('user_id', $userId)->delete();
    }
}
