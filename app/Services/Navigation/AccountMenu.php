<?php

namespace App\Services\Navigation;

use App\Services\Shop\OrderTracking;
use App\User;

/** Browser navigation only; never loads billing providers or changes account access. */
class AccountMenu
{
    public function shortcuts(?User $account): array
    {
        $host = request()->getHost();
        $hasMembership = $account && ($account->relationLoaded('membership')
            ? $account->membership !== null : $account->membership()->exists());
        $hasAccess = $hasMembership || ($account && $account->super_user);

        return [
            'membership' => $host === parse_url(route('webapp.discover'), PHP_URL_HOST) || $hasAccess,
            'membershipUrl' => route($hasMembership ? 'webapp.membership.edit' : ($hasAccess ? 'webapp.settings' : 'webapp.membership.pricing')),
            'downloads' => $account && ($host === parse_url(route('learn.home'), PHP_URL_HOST) || $account->purchases()->exists()),
            'orders' => $account && ($host === parse_url(route('shop.home'), PHP_URL_HOST)
                || app(OrderTracking::class)->visible()->where('user_id', $account->id)->exists()),
        ];
    }
}
