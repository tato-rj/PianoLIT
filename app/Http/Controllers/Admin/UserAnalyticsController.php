<?php

namespace App\Http\Controllers\Admin;

use App\Activity\{Filters, Insights, Journey, People, Queries, VisitSort};
use App\Http\Controllers\Controller;
use App\User;
use App\Support\AdminPagination;
use Illuminate\Http\Request;

class UserAnalyticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(Request $request, Queries $queries)
    {
        $filters = new Filters($request);
        $visitSort = new VisitSort($request);
        $metrics = $queries->metrics($filters);
        $trend = $queries->trend($filters);
        $visits = $queries->visitList($filters, null, AdminPagination::PER_PAGE, $visitSort);
        $ungroupedCount = $queries->ungrouped($filters)->count();
        $history = $request->boolean('history') ? $queries->ungrouped($filters)->orderByDesc('occurred_at')->orderByDesc('id')->paginate(AdminPagination::PER_PAGE, ['id', 'user_id', 'platform', 'type', 'source', 'occurred_at', 'occurred_at_utc', 'received_at', 'received_at_utc', 'route_name', 'url'], 'history_page')->withQueryString() : null;
        return view('admin.pages.analytics.users.activity', compact('filters', 'visitSort', 'metrics', 'trend', 'visits', 'ungroupedCount', 'history'));
    }

    public function people(Request $request, People $people)
    {
        return view('admin.pages.analytics.users.people', ['people' => $people->directory($request)]);
    }

    public function insights(Request $request, Insights $insights)
    {
        $filters = new Filters($request);
        return view('admin.pages.analytics.users.insights', array_merge($insights->get($filters), ['filters' => $filters]));
    }

    public function profile(Request $request, User $user, People $people, $tab = 'overview')
    {
        abort_unless(in_array($tab, ['overview', 'visits', 'music', 'account'], true), 404);
        return view('admin.pages.analytics.users.profile', $people->profile($user, $tab, new Filters($request)));
    }

    public function journey($visit, Journey $journey)
    {
        return view('admin.pages.analytics.users.journey', $journey->get($visit));
    }
}
