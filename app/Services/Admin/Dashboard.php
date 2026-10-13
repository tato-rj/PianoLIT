<?php

namespace App\Services\Admin;

use App\{User, Piece, Subscription};
use App\Blog\Post;
use App\Quiz\Quiz;
use App\Infograph\Infograph;
use App\CrashCourse\CrashCourse;
use App\Shop\{eBook, eScore};
use Illuminate\Support\Facades\DB;

class Dashboard
{
    public function data(int $days, string $platform): array
    {
        $end = now();
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        $previousStart = $start->copy()->subDays($days);
        // Keep timer/visibility bookkeeping out of the activity totals.
        $events = DB::table('activity_events')->whereIn('platform', ['web', 'webapp', 'app'])
            ->whereNotIn('type', ['heartbeat', 'page_ready', 'page_checkpoint', 'page_hidden', 'page_visible', 'page_leave', 'page_restore', 'media_progress'])
            ->where('occurred_at', '>=', $previousStart)->where('occurred_at', '<=', $end);
        if ($platform !== 'all') $events->where('platform', $platform);

        $daily = (clone $events)->selectRaw('DATE(occurred_at) as date, platform, COUNT(*) as total')
            ->groupBy('date', 'platform')->get();
        $current = $daily->where('date', '>=', $start->toDateString());
        $total = (int) $current->sum('total');
        $previous = (int) $daily->where('date', '<', $start->toDateString())->sum('total');
        $trend = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $trend[] = ['date' => $date->format('M j'), 'label' => $date->format('l, F j'),
                'total' => (int) $current->where('date', $date->toDateString())->sum('total')];
        }

        // Only current session-attributed web events establish the ranking.
        // Legacy app/imported user IDs are analytics associations, not verified identity.
        $ranking = (clone $events)->where('occurred_at', '>=', $start)
            ->whereIn('platform', ['web', 'webapp'])->whereIn('source', ['server', 'browser'])
            ->join('users', 'users.id', '=', 'activity_events.user_id');
        $activeUsers = (clone $ranking)->distinct()->count('users.id');
        $engagedUsers = (clone $ranking)
            ->select('users.id', 'users.first_name', 'users.last_name')
            ->selectRaw('COUNT(*) as events_count')
            ->groupBy('users.id', 'users.first_name', 'users.last_name')
            ->orderByDesc('events_count')->orderBy('users.id')->limit(6)->get();

        $definitions = [
            [User::query(), 'Total users', 'users', 'admin.users.index'],
            [Piece::query(), 'Pieces in repertoire', 'music', 'admin.pieces.index'],
            [Subscription::query(), 'Email subscribers', 'mail', 'admin.subscriptions.index'],
            [Post::published(), 'Published posts', 'newspaper', 'admin.posts.index'],
        ];
        $metrics = [];
        foreach ($definitions as [$query, $label, $icon, $route]) {
            $dateColumn = $label === 'Published posts' ? 'published_at' : 'created_at';
            // Model defaults such as $withCount already selected users.* / pieces.*.
            // Apply eligibility scopes, then replace those columns with aggregates only.
            $row = $query->toBase()->select([])
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN '.$dateColumn.' >= ? AND '.$dateColumn.' <= ? THEN 1 ELSE 0 END) as added', [$start, $end])->first();
            $metrics[] = ['label' => $label, 'icon' => $icon, 'url' => route($route),
                'total' => (int) $row->total, 'added' => (int) $row->added,
                'share' => $row->total ? round($row->added / $row->total * 100) : 0];
        }
        $library = [];
        foreach ([[Quiz::class, 'Quizzes', 'circle-help', 'admin.quizzes.index'],
            [Infograph::class, 'Infographics', 'pencil-ruler', 'admin.infographs.index'],
            [eBook::class, 'eBooks', 'book-open', 'admin.ebooks.index'],
            [eScore::class, 'eScores', 'file-text', 'admin.escores.index'],
            [CrashCourse::class, 'Crash courses', 'graduation-cap', 'admin.crashcourses.index']] as [$model, $label, $icon, $route]) {
            $library[] = ['label' => $label, 'icon' => $icon, 'total' => $model::published()->count(), 'url' => route($route)];
        }

        return compact('days', 'platform', 'start', 'end', 'total', 'previous', 'trend', 'activeUsers', 'engagedUsers', 'metrics', 'library') + [
            'userCountries' => app(UserCountries::class)->data(),
            'platforms' => collect(['web' => 'Website', 'webapp' => 'Web app', 'app' => 'iOS app'])->map(function ($label, $key) use ($current, $total) {
                $count = (int) $current->where('platform', $key)->sum('total');
                return ['label' => $label, 'total' => $count, 'share' => $total ? round($count / $total * 100) : 0];
            }),
            'change' => $previous ? round(($total - $previous) / $previous * 100) : null,
        ];
    }
}
