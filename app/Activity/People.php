<?php

namespace App\Activity;

use App\User;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class People
{
    public static function membershipCase()
    {
        // Reporting only; content authorization continues to use HasMembership.
        return "CASE WHEN users.super_user = 1 THEN 'Super user'
            WHEN sm.id IS NOT NULL AND sm.ended_at IS NULL AND sm.membership_ends_at > ? THEN 'Grace period'
            WHEN sm.id IS NOT NULL AND sm.ended_at IS NULL AND sm.membership_ends_at IS NULL AND sm.renews_at >= ? AND sm.paused_at IS NULL AND sm.status IN ('trialing','trial') THEN 'Trial'
            WHEN sm.id IS NOT NULL AND sm.ended_at IS NULL AND sm.membership_ends_at IS NULL AND sm.renews_at >= ? AND sm.paused_at IS NULL AND sm.status = 'active' THEN 'Active'
            WHEN am.id IS NOT NULL AND am.renews_at >= ? THEN 'Active'
            WHEN m.id IS NOT NULL THEN 'Expired or inactive' ELSE 'No membership' END";
    }

    public function directory(Request $request)
    {
        $input = $request->validate([
            'search' => 'nullable|string|max:160', 'origin' => 'nullable|in:web,webapp,ios,android',
            'country' => 'nullable|string|size:2', 'status' => 'nullable|in:Active,Trial,Grace period,Super user,Expired or inactive,No membership',
            'activity' => 'nullable|in:none,one,repeat', 'sort' => 'nullable|in:registered,last_active,engagement',
            'registered_from' => 'nullable|date_format:Y-m-d', 'registered_to' => 'nullable|date_format:Y-m-d'.($request->filled('registered_from') ? '|after_or_equal:registered_from' : ''),
            'active_from' => 'nullable|date_format:Y-m-d', 'active_to' => 'nullable|date_format:Y-m-d'.($request->filled('active_from') ? '|after_or_equal:active_from' : ''),
        ]);
        $queries = new Queries;
        $visits = $queries->visits()->whereRaw(Queries::account().' IS NOT NULL')->selectRaw(Queries::account().' as account_id, COUNT(*) as visits_count, MAX(v.last_activity_at) as last_activity')->groupByRaw(Queries::account());
        $opens = $queries->pieceOpens(ExcludedAccounts::events(DB::table('activity_events as e')->join('activity_visits as v', 'v.id', '=', 'e.visit_id')->leftJoin('activity_visit_links as l', 'l.visit_id', '=', 'v.id'), 'e'))
            ->whereRaw(Queries::account().' IS NOT NULL')->selectRaw(Queries::account().' as account_id, COUNT(*) as pieces_count')->groupByRaw(Queries::account());
        $query = ExcludedAccounts::users(DB::table('users'))->leftJoinSub($visits, 'a', 'a.account_id', '=', 'users.id')->leftJoinSub($opens, 'o', 'o.account_id', '=', 'users.id')
            ->leftJoin('locations as location', 'location.user_id', '=', 'users.id')
            ->leftJoinSub(DB::table('memberships')->select('user_id')->selectRaw('MIN(id) as membership_id')->groupBy('user_id'), 'member', 'member.user_id', '=', 'users.id')
            ->leftJoin('memberships as m', 'm.id', '=', 'member.membership_id')
            ->leftJoin('stripe_memberships as sm', function ($join) { $join->on('sm.id', '=', 'm.source_id')->where('m.source_type', 'App\\Billing\\Sources\\Stripe'); })
            ->leftJoin('apple_memberships as am', function ($join) { $join->on('am.id', '=', 'm.source_id')->where('m.source_type', 'App\\Billing\\Sources\\Apple'); })
            ->select('users.id', 'users.first_name', 'users.last_name', 'users.email', 'users.origin', 'users.created_at', 'a.last_activity', 'location.countryName')
            ->selectRaw('COALESCE(a.visits_count, 0) as visits_count, COALESCE(o.pieces_count, 0) as pieces_count')
            ->selectRaw(self::membershipCase().' as membership_status', array_fill(0, 4, now()));
        if (!empty($input['search'])) $query->where(function ($q) use ($input) { $value = '%'.$input['search'].'%'; $q->where('users.first_name', 'like', $value)->orWhere('users.last_name', 'like', $value)->orWhere('users.email', 'like', $value); });
        if (!empty($input['origin'])) $query->where('users.origin', $input['origin']);
        if (!empty($input['country'])) $query->where('location.countryCode', strtoupper($input['country']));
        if (!empty($input['status'])) $query->whereRaw('('.self::membershipCase().') = ?', array_merge(array_fill(0, 4, now()), [$input['status']]));
        foreach (['registered' => 'users.created_at', 'active' => 'a.last_activity'] as $key => $column) {
            if (!empty($input[$key.'_from'])) $query->where($column, '>=', $input[$key.'_from']);
            if (!empty($input[$key.'_to'])) $query->where($column, '<', carbon($input[$key.'_to'])->addDay());
        }
        if (($input['activity'] ?? '') === 'none') $query->whereNull('a.account_id');
        if (($input['activity'] ?? '') === 'one') $query->where('a.visits_count', 1);
        if (($input['activity'] ?? '') === 'repeat') $query->where('a.visits_count', '>', 1);
        $sort = ['registered' => 'users.created_at', 'last_active' => 'a.last_activity', 'engagement' => 'pieces_count'][$input['sort'] ?? 'last_active'];
        return $query->orderByDesc($sort)->orderByDesc('users.id')->paginate(AdminPagination::PER_PAGE)->withQueryString();
    }

    public function profile(User $user, $tab, Filters $filters)
    {
        abort_if(ExcludedAccounts::user($user), 404);
        request()->validate(['folder' => 'nullable|integer|min:1']);
        $visitSort = new VisitSort(request());
        $user->loadMissing(['membership.source', 'location']);
        $queries = new Queries;
        $base = $queries->visits(null, $user->id);
        $stats = (clone $base)->selectRaw('COUNT(*) as visits, MIN(v.started_at) as first_visit, MAX(v.last_activity_at) as last_activity')->first();
        $stats->pieces = $queries->pieceOpens($queries->events(null, $user->id))->count();
        $stats->favorites = DB::table('favorites')->where('user_id', $user->id)->distinct()->count('piece_id');
        $stats->folders = DB::table('favorite_folders')->where('user_id', $user->id)->count();
        $stats->scores = DB::table('score_annotations')->where('user_id', $user->id)->where('marks', '!=', '[]')->distinct()->count('piece_id');
        $ungrouped = $queries->ungrouped(null, $user->id)->selectRaw('COUNT(*) as total, MAX(occurred_at) as last_activity')->first();
        $stats->ungrouped = (int) $ungrouped->total;
        $stats->last_ungrouped_activity = $ungrouped->last_activity;
        $history = $tab === 'visits' ? $queries->ungrouped($filters, $user->id)->orderByDesc('occurred_at')->orderByDesc('id')
            ->paginate(AdminPagination::PER_PAGE, ['id', 'platform', 'type', 'source', 'occurred_at', 'occurred_at_utc', 'route_name', 'url'], 'history_page')->withQueryString() : null;
        $historyParameters = ['user' => $user->id, 'tab' => 'visits'];
        if ($stats->last_ungrouped_activity) {
            $historyDay = carbon($stats->last_ungrouped_activity)->toDateString();
            $historyParameters += ['range' => 'custom', 'from' => $historyDay, 'to' => $historyDay, 'platform' => 'all'];
        }
        $ungroupedHistoryUrl = route('admin.analytics.users.profile', $historyParameters).'#ungrouped-history';
        $engagement = $queries->events(null, $user->id)->whereIn('e.type', ['media_play', 'score_opened', 'annotation_saved', 'favorite_changed'])->select('e.type')->selectRaw('COUNT(*) as total')->groupBy('e.type')->get()->pluck('total', 'type');
        $engagement['synthesia'] = $queries->events(null, $user->id)->where('e.type', 'media_play')->where('e.metadata->media_id', 'like', '%synthesia%')->count();
        $visits = $queries->visitList($tab === 'visits' ? $filters : null, $user->id, AdminPagination::PER_PAGE, $visitSort);
        $frequent = $queries->pieceOpens($queries->events(null, $user->id))->select('e.resource_id')->selectRaw('COUNT(*) as total, MAX(e.occurred_at) as last_opened')->groupBy('e.resource_id')->orderByDesc('total')->limit(8)->get();
        $music = [];
        $selectedFolder = null;
        if ($tab === 'music') {
            if (request('folder')) {
                $selectedFolder = DB::table('favorite_folders')->where('user_id', $user->id)->where('id', request('folder'))->first();
                abort_unless($selectedFolder, 404);
            }
            $music['recent'] = $queries->pieceOpens($queries->events(null, $user->id))->select('e.resource_id')->selectRaw('COUNT(*) as total, MAX(e.occurred_at) as last_opened')->groupBy('e.resource_id')->orderByDesc('last_opened')->paginate(AdminPagination::PER_PAGE, ['*'], 'music_page')->withQueryString();
            $saved = DB::table('favorites')->where('user_id', $user->id);
            if ($selectedFolder) $saved->where('favorite_folder_id', $selectedFolder->id);
            $music['favorites'] = $saved->select('piece_id')->selectRaw('MAX(created_at) as saved_at')->groupBy('piece_id')->orderByDesc('saved_at')->paginate(AdminPagination::PER_PAGE, ['*'], 'favorites_page')->withQueryString();
            $music['folders'] = DB::table('favorite_folders as f')->where('f.user_id', $user->id)->select('f.id', 'f.name', 'f.updated_at')->selectSub(DB::table('favorites')->whereColumn('favorite_folder_id', 'f.id')->selectRaw('COUNT(*)'), 'pieces_count')->orderByDesc('f.updated_at')->paginate(AdminPagination::PER_PAGE, ['*'], 'folders_page')->withQueryString();
            $music['annotations'] = DB::table('score_annotations')->where('user_id', $user->id)->where('marks', '!=', '[]')->select('piece_id', 'revision', 'updated_at')->orderByDesc('updated_at')->paginate(AdminPagination::PER_PAGE, ['*'], 'scores_page')->withQueryString();
            $music['media'] = $queries->events(null, $user->id)->whereIn('e.type', ['media_progress', 'media_end', 'media_pause'])->orderByDesc('e.occurred_at')->orderByDesc('e.id')->paginate(AdminPagination::PER_PAGE, ['e.id', 'e.type', 'e.metadata', 'e.occurred_at', 'e.occurred_at_utc', 'e.received_at', 'e.received_at_utc'], 'media_page')->withQueryString();
        }
        $ids = $frequent->pluck('resource_id');
        foreach (['recent' => 'resource_id', 'favorites' => 'piece_id', 'annotations' => 'piece_id'] as $key => $column) if (isset($music[$key])) $ids = $ids->merge($music[$key]->pluck($column));
        if (isset($music['media'])) $ids = $ids->merge(collect($music['media']->items())->map(function ($e) { return Presentation::metadata($e)['piece_id'] ?? null; }));
        $pieces = Queries::pieces($ids->filter()->unique());
        $accountEvents = $tab === 'account' ? DB::table('activity_events as e')->where('e.user_id', $user->id)->where('e.source', 'server')->whereIn('e.type', ['registration_completed', 'login', 'trial_started', 'subscription_activated', 'subscription_paid', 'subscription_canceled'])->orderByDesc('e.occurred_at')->paginate(AdminPagination::PER_PAGE, ['e.*'], 'account_page')->withQueryString() : null;
        return compact('user', 'tab', 'filters', 'visitSort', 'stats', 'engagement', 'visits', 'history', 'ungroupedHistoryUrl', 'frequent', 'music', 'pieces', 'accountEvents', 'selectedFolder');
    }
}
