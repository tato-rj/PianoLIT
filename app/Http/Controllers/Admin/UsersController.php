<?php

namespace App\Http\Controllers\Admin;

use App\{Admin, User, Piece, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Analytics\Analytics;
use Illuminate\Support\Facades\DB;

class UsersController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return datatable(User::query()->with(['location', 'membership.source']))
                ->withDate()
                ->checkable()
                ->withBlade([
                    'name' => view('admin.pages.users.table.name'),
                    'origin_display' => view('admin.pages.users.table.origin'),
                    'status' => view('admin.pages.users.table.status'),
                    'super_user_display' => view('admin.pages.users.table.super-user'),
                    'actions' => view('admin.pages.users.table.actions'),
                ])
                ->make();
        }

        return view('admin.pages.users.index');
    }

    public function show(User $user)
    {
        if (request('format') == 'json')
            return $user->membership()->exists() ? $user->membership->source : null;

        return view('admin.pages.users.show.index', [
            'user' => $user->load('favorites'),
            'activityCounts' => DB::table('activity_events')->where('user_id', $user->id)->selectRaw('platform, COUNT(*) as total')->groupBy('platform')->pluck('total', 'platform'),
            'appLogs' => DB::table('activity_events')->where('user_id', $user->id)->where('platform', 'app')->orderByDesc('occurred_at')->orderByDesc('id')->take(5)->get(),
        ]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted.');
    }

    public function logs()
    {
        if (request()->ajax() && request()->has('type'))
            return (new Analytics)->for('logs')->query(request('type'), request()->except('type'))->get();

        if (request()->ajax())
            return $this->logsDatatable(request());

        return view('admin.pages.users.logs.index');
    }

    protected function logsDatatable(Request $request)
    {
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $orderColumn = (int) $request->input('order.0.column', 7);
        $sortField = $request->input("columns.{$orderColumn}.data", 'last_active');
        $search = trim($request->input('search.value', ''));
        $activity = DB::table('activity_events')->whereNotNull('user_id')->selectRaw('user_id, COUNT(*) as activity_count, MAX(occurred_at) as activity_last')->groupBy('user_id');
        $query = User::query()->select('users.*', 'activity.activity_count', 'activity.activity_last')
            ->leftJoinSub($activity, 'activity', 'activity.user_id', '=', 'users.id')
            ->with(['location', 'membership.source']);
        $recordsTotal = User::count();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('users.id', $search)
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $columns = [
            'created_at' => 'users.created_at', 'id' => 'users.id', 'name' => 'users.first_name',
            'favorites_count' => 'favorites_count', 'origin' => 'users.origin',
            'visits' => 'activity.activity_count', 'last_active' => 'activity.activity_last',
        ];
        $column = $columns[$sortField] ?? 'activity.activity_last';
        $users = $query->orderBy($column, $direction)->orderBy('users.id')->skip($start)->take($length)->get();
        $totalVisits = DB::table('activity_events')->whereNotNull('user_id')->count();
        $data = $users->map(function ($user) use ($totalVisits) {
            $visits = (int) $user->activity_count;
            $lastActive = $user->activity_last ? carbon($user->activity_last) : null;
            $topUser = $totalVisits && ($visits * 100 / $totalVisits) >= 20;

            return [
                'created_at' => '<span class="invisible position-absolute">'.$user->created_at->timestamp.'</span>'.$user->created_at->toFormattedDateString(),
                'id' => $user->id,
                'name' => view('admin.pages.users.logs.table.name', compact('user', 'topUser'))->render(),
                'visits' => $visits,
                'favorites_count' => $user->favorites_count,
                'origin' => view('admin.pages.users.table.origin', ['item' => $user])->render(),
                'status' => view('admin.pages.users.table.status', ['item' => $user])->render(),
                'last_active' => view('admin.pages.users.logs.table.activity', compact('lastActive'))->render(),
                'actions' => view('admin.pages.users.logs.table.actions', ['item' => $user])->render(),
                'DT_RowAttr' => ['style' => $topUser ? 'background-color: #d7f3e33d' : null],
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function loadLogs(User $user, Request $request)
    {
        $request->validate(['type' => 'required|in:app,web,webapp', 'start_at' => 'nullable|integer|min:0']);
        $type = $request->type;
        $events = DB::table('activity_events')->where('user_id', $user->id)->where('platform', $type)
            ->orderByDesc('occurred_at')->orderByDesc('id')->skip((int) $request->start_at)->take(5)->get();
        if ($type === 'app') {
            return view('admin.pages.users.show.logs.app-rows', ['logs' => $events])->render();
        }
        return view('admin.pages.activity.events', ['events' => $events, 'visit' => null])->render();
    }

    public function loadFavorites(User $user, Request $request)
    {
        $pieces = $user->favorites->slice($request->start_at)->take(5);

        return view('admin.pages.users.show.favorites.rows', ['pieces' => $pieces])->render();
    }

    public function loadRequests(User $user, Request $request)
    {
        $requests = $user->tutorialRequests->slice($request->start_at)->take(5);

        return view('admin.pages.users.show.requests.rows', ['requests' => $requests])->render();
    }

    public function destroyMany(Request $request)
    {        
        foreach (json_decode($request->ids) as $id) {
            User::findOrFail($id)->delete();
        }

        return redirect(route('admin.users.index'))->with('status', 'The users have been successfully deleted.');
    }

    public function purge(User $user)
    {
        DB::transaction(function () use ($user) {
            foreach (['activity_events', 'activity_pages', 'activity_visits'] as $table) {
                DB::table($table)->where('user_id', $user->id)->orWhereIn($table === 'activity_visits' ? 'id' : 'visit_id',
                    DB::table('activity_visit_links')->where('user_id', $user->id)->select('visit_id'))->delete();
            }
        });

        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted and its logs removed.');
    }
}
