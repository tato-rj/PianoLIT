<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Billing\Membership;

class MembershipsController extends Controller
{
    public function monitor()
    {
        return redirect()->route('admin.analytics.memberships', ['provider' => 'apple']);
    }

    public function loadTrials(Request $request)
    {
        $memberships = \App\Activity\ExcludedAccounts::owned(Membership::query())->trial()->newest()->get()->slice($request->start_at)->take(5);

        return view('admin.pages.analytics.memberships.trials', compact('memberships'))->render();
    }

    public function loadMembers(Request $request)
    {
        $memberships = \App\Activity\ExcludedAccounts::owned(Membership::query())->member()->get()->sortBy('source.renews_at')->slice($request->start_at)->take(5);

        return view('admin.pages.analytics.memberships.members', compact('memberships'))->render();
    }

    public function loadExpired(Request $request)
    {
        $memberships = \App\Activity\ExcludedAccounts::owned(Membership::query())->expired()->get()->sortByDesc('source.renews_at')->slice($request->start_at)->take(5);

        return view('admin.pages.analytics.memberships.expired', compact('memberships'))->render();
    }
}
