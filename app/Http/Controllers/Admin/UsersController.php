<?php

namespace App\Http\Controllers\Admin;

use App\{Admin, User, Piece, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Log\Loggers\DailyLog;
use Illuminate\Support\Facades\Redis;
use App\Stats\Stats;
use App\Log\UserLogIndex;

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

        return view('admin.pages.users.show.index', ['user' => $user->load('favorites')]);
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted.');
    }

    public function logs()
    {
        if (request()->ajax() && request()->has('type'))
            return (new Stats)->for('logs')->query(request('type'), request()->except('type'))->get();

        if (request()->ajax())
            return $this->logsDatatable(request());

        return view('admin.pages.users.logs.index', [
            'logIndexReady' => (new UserLogIndex)->isReady(),
        ]);
    }

    protected function logsDatatable(Request $request)
    {
        $index = new UserLogIndex;
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $orderColumn = (int) $request->input('order.0.column', 7);
        $sortField = $request->input("columns.{$orderColumn}.data", 'last_active');
        $search = trim($request->input('search.value', ''));
        $query = User::query()->with(['location', 'membership.source']);
        $recordsTotal = User::count();

        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%");
            });
        }

        $recordsFiltered = (clone $query)->count();
        $rankedField = $sortField === 'visits' ? 'visits' : ($sortField === 'last_active' ? 'last_active' : null);

        if ($index->isReady() && $rankedField && $search === '') {
            $ids = $index->rankedIds($rankedField, $start, $length, $direction);
            $usersById = $query->whereIn('id', $ids)->get()->keyBy('id');
            $users = collect($ids)->map(function ($id) use ($usersById) {
                return $usersById->get($id);
            })->filter();
        } elseif ($index->isReady() && $rankedField) {
            $ids = $query->pluck('id')->all();
            $stats = $index->statsFor($ids);
            $stat = $rankedField === 'visits' ? 'visits' : 'last_active';
            usort($ids, function ($left, $right) use ($stats, $stat, $direction) {
                $comparison = $stats[$left][$stat] <=> $stats[$right][$stat];
                return $direction === 'asc' ? $comparison : -$comparison;
            });
            $ids = array_slice($ids, $start, $length);
            $usersById = User::with(['location', 'membership.source'])->whereIn('id', $ids)->get()->keyBy('id');
            $users = collect($ids)->map(function ($id) use ($usersById) {
                return $usersById->get($id);
            })->filter();
        } else {
            $columns = [
                'created_at' => 'created_at',
                'id' => 'id',
                'name' => 'first_name',
                'favorites_count' => 'favorites_count',
                'origin' => 'origin',
            ];
            $column = $columns[$sortField] ?? 'created_at';
            $users = $query->orderBy($column, $direction)->skip($start)->take($length)->get();
        }

        $totalVisits = $index->isReady() ? $index->total() : 0;
        $indexedStats = $index->isReady() ? $index->statsFor($users->pluck('id')->all()) : [];
        $data = $users->map(function ($user) use ($totalVisits, $indexedStats) {
            $visits = isset($indexedStats[$user->id]) ? $indexedStats[$user->id]['visits'] : $user->logs_count;
            if (isset($indexedStats[$user->id])) {
                $lastActiveTimestamp = $indexedStats[$user->id]['last_active'];
                $lastActive = $lastActiveTimestamp ? carbon($lastActiveTimestamp) : null;
            } else {
                $lastActive = $user->lastActive();
            }
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
        $type = $request->type;

        $array = $user->log()->$type;

        $logs = collect(array_slice($array, $request->start_at, count($array), true))->take(5);

        return view('admin.pages.users.show.logs.' . $type . '-rows', ['logs' => $logs])->render();
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
        Redis::del($user->redisKey('app'));
        Redis::del($user->redisKey('web'));
        \Artisan::call('redis:refresh-daily-logs');

        $user->delete();

        return redirect(route('admin.users.index'))->with('status', 'The user has been successfully deleted and its logs removed.');
    }
}
