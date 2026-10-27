<?php

namespace App\Http\Controllers\Admin;

use App\Activity\{ExcludedAccounts, Filters, Insights, Journey, PageIdentity, People, Queries, VisitRemoval, VisitSort};
use App\Http\Controllers\Controller;
use App\User;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $iosOnly = $filters->platforms === ['app'];
        $appActivity = $filters->includesPlatform('app') ? $queries->appRequestSummary($filters) : null;
        $metrics = $iosOnly ? ['iOS requests' => $appActivity->requests, 'Reported accounts' => $appActivity->reported_accounts, 'Days with requests' => $appActivity->active_days] : $queries->metrics($filters);
        if (!$iosOnly && $appActivity) $metrics['iOS requests'] = $appActivity->requests;
        $series = $iosOnly ? [] : ['total' => ['Visits', 'visits'], 'pieces' => ['Pieces opened', 'pieces'], 'media_starts' => ['Media starts', 'media']];
        if ($appActivity) $series['ios_requests'] = ['iOS requests', 'app'];
        $trend = $queries->trend($filters);
        $visits = $queries->visitList($filters, null, AdminPagination::PER_PAGE, $visitSort);
        $ungroupedCount = $queries->ungrouped($filters)->count();
        $historyQuery = $iosOnly ? $queries->appRequests($filters) : $queries->ungrouped($filters);
        $history = $iosOnly || $request->boolean('history') ? $historyQuery->orderByDesc('occurred_at')->orderByDesc('id')->paginate(AdminPagination::PER_PAGE, ['id', 'user_id', 'platform', 'type', 'source', 'occurred_at', 'occurred_at_utc', 'received_at', 'received_at_utc', 'route_name', 'url'], 'history_page')->withQueryString() : null;
        // Resolve display details only for this page; a submitted legacy ID
        // remains an unverified association, never an authenticated identity.
        $historyUsers = $history ? ExcludedAccounts::users(DB::table('users'))->whereIn('id', $history->pluck('user_id')->filter()->unique())
            ->get(['id', 'first_name', 'last_name', 'email'])->keyBy('id') : collect();
        return view('admin.pages.analytics.users.activity', compact('filters', 'visitSort', 'metrics', 'series', 'iosOnly', 'appActivity', 'trend', 'visits', 'ungroupedCount', 'history', 'historyUsers'));
    }

    public function people(Request $request, People $people)
    {
        return view('admin.pages.analytics.users.people', ['people' => $people->directory($request)]);
    }

    public function insights(Request $request, Insights $insights)
    {
        $request->validate(['page_hub' => 'nullable|in:all,'.implode(',', array_keys(PageIdentity::HUBS))]);
        // Traffic reports include guests by default. Explicit audience filters
        // and the account-focused defaults on other analytics screens remain.
        if (!$request->has('audience')) $request->merge(['audience' => 'all']);
        $filters = new Filters($request);
        return view('admin.pages.analytics.users.insights', array_merge($insights->get($filters, $request->input('page_hub') ?: 'all'), ['filters' => $filters]));
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

    public function destroyVisit($visit, VisitRemoval $removal)
    {
        $removal->delete($visit);

        return redirect()->route('admin.analytics.users')->with('status', 'Visit deleted.');
    }
}
