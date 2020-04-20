<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Membership;

class MembershipsController extends Controller
{
    public function loadTrials(Request $request)
    {
        $memberships = Membership::trial()->newest()->get()->slice($request->start_at)->take(5);

        return view('admin.pages.stats.memberships.trials', compact('memberships'))->render();
    }

    public function loadMembers(Request $request)
    {
        $memberships = Membership::member()->lastRenewed()->get()->slice($request->start_at)->take(5);

        return view('admin.pages.stats.memberships.members', compact('memberships'))->render();
    }

    public function loadExpired(Request $request)
    {
        $memberships = Membership::expired()->lastRenewed('DESC')->get()->slice($request->start_at)->take(5);

        return view('admin.pages.stats.memberships.expired', compact('memberships'))->render();
    }
}
