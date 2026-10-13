<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['guests' => 'sometimes|boolean']);
        return $this->history($request);
    }

    public function user(User $user, Request $request)
    {
        return $this->history($request, $user);
    }

    private function history(Request $request, User $user = null)
    {
        $visits = DB::table('activity_visits as visits')->leftJoin('users', 'users.id', '=', 'visits.user_id')
            ->select('visits.*', 'users.first_name', 'users.last_name')
            ->selectSub(DB::table('activity_events')->selectRaw('COUNT(*)')->whereColumn('visit_id', 'visits.id'), 'events_count');
        if ($user) $visits->where('visits.user_id', $user->id);
        if ($request->boolean('guests')) $visits->whereNull('visits.user_id');
        $visits = $visits->orderByDesc('visits.started_at')->orderBy('visits.id')->paginate(AdminPagination::PER_PAGE, ['*'], 'visits_page');
        $legacy = DB::table('activity_events')->whereNull('visit_id');
        if ($user) $legacy->where('user_id', $user->id);
        if ($request->boolean('guests')) $legacy->whereNull('user_id');
        $legacy = $legacy->orderByDesc('occurred_at')->orderByDesc('id')->paginate(AdminPagination::PER_PAGE, ['*'], 'history_page');
        return view('admin.pages.activity.index', compact('visits', 'legacy', 'user'));
    }

    public function show($visit)
    {
        return view('admin.pages.analytics.users.journey', (new \App\Activity\Journey)->get($visit));
    }
}
