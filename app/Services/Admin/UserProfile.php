<?php

namespace App\Services\Admin;

use App\User;
use App\Activity\ExcludedAccounts;
use App\Billing\Sources\{Apple, Stripe};
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserProfile
{
    public function get(User $user, Request $request)
    {
        $request->validate(['favorites_page' => 'sometimes|integer|min:1|max:1000000', 'requests_page' => 'sometimes|integer|min:1|max:1000000']);
        $user->load(['location', 'membership']);
        $membership = $user->membership;
        $source = null;
        if ($membership && in_array($membership->source_type, [Stripe::class, Apple::class], true)) {
            $columns = $membership->source_type === Stripe::class
                ? ['id', 'stripe_id', 'plan', 'status', 'card_brand', 'card_last_four', 'created_at', 'updated_at', 'renews_at', 'membership_ends_at', 'paused_at', 'canceled_at', 'ended_at']
                : ['id', 'plan', 'latest_receipt_info', 'created_at', 'updated_at', 'renews_at', 'validated_at'];
            $source = $membership->source_type::select($columns)->find($membership->source_id);
        }
        if ($membership) $membership->setRelation('source', $source);
        $billingUser = clone $user;
        $billingUser->super_user = false;
        $billingAccess = $billingUser->hasActiveSubscription();
        $onTrial = $source instanceof Stripe ? $source->isOnTrial() : ($source instanceof Apple && in_array(data_get($source->latest_receipt_info, 'is_trial_period'), [true, 'true', 1, '1'], true));
        $billingStatus = !$source ? ($membership ? 'Membership unavailable' : 'Free account') :
            ($billingAccess ? ($source instanceof Stripe && $source->membership_ends_at ? 'Grace period' : ($onTrial ? 'On trial' : 'Active membership')) :
                ($source->isPaused() ? 'Paused' : ($source->isEnded() ? 'Ended or inactive' : 'Expired')));
        $hasAccess = $user->hasActiveSubscription();
        $status = $user->super_user ? 'Super user' : $billingStatus;
        $appleCheck = $source instanceof Apple ? DB::table('apple_membership_checks')->where('apple_membership_id', $source->id)
            ->first(['status', 'environment', 'auto_renews', 'checked_at', 'verified_at', 'expires_at']) : null;

        $links = DB::table('user_auth_providers')->where('user_id', $user->id)->orderBy('provider')
            ->get(['provider', 'provider_email', 'created_at', 'unusable_password_hash']);
        $hasPassword = (bool) $user->password && !$links->contains(function ($link) use ($user) {
            return $link->unusable_password_hash && hash_equals($link->unusable_password_hash, $user->password);
        });
        $signInMethods = $links->map(function ($link) {
            return ['label' => ucfirst($link->provider), 'email' => $link->provider_email, 'linked_at' => $link->created_at];
        });
        if ($hasPassword) $signInMethods->prepend(['label' => 'Email & password', 'email' => $user->email, 'linked_at' => null]);
        $newsletter = $user->subscription()->with('lists')->first();

        $favorites = DB::table('favorites as f')->join('pieces as p', 'p.id', '=', 'f.piece_id')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->where('f.user_id', $user->id)->select('p.id', 'p.name', 'c.name as composer')
            ->selectRaw('MAX(f.created_at) as saved_at')->groupBy('p.id', 'p.name', 'c.name')
            ->orderByDesc('saved_at')->orderByDesc('p.id')->paginate(AdminPagination::PER_PAGE, ['*'], 'favorites_page')->withQueryString();
        $requests = DB::table('tutorial_requests as r')->leftJoin('pieces as p', 'p.id', '=', 'r.piece_id')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->where('r.user_id', $user->id)->select('r.id', 'r.piece_id', 'r.created_at', 'r.published_at', 'r.types', 'p.name', 'c.name as composer')
            ->orderByDesc('r.created_at')->orderByDesc('r.id')->paginate(AdminPagination::PER_PAGE, ['*'], 'requests_page')->withQueryString();
        $counts = [
            'favorites' => $favorites->total(),
            'folders' => DB::table('favorite_folders')->where('user_id', $user->id)->count(),
            'scores' => DB::table('score_annotations')->where('user_id', $user->id)->where('marks', '!=', '[]')->distinct()->count('piece_id'),
            'requests' => $requests->total(),
        ];
        $analyticsExcluded = ExcludedAccounts::user($user);
        $removalBlocked = (new UserRemovalPolicy)->reason($user->id);
        $formatDate = function ($value) { return $value ? carbon($value)->format('M j, Y · g:i A') : 'Not recorded'; };
        return compact('user', 'source', 'status', 'hasAccess', 'billingStatus', 'billingAccess', 'appleCheck', 'signInMethods', 'newsletter', 'favorites', 'requests', 'counts', 'analyticsExcluded', 'removalBlocked', 'formatDate');
    }
}
