<?php

namespace App\Activity;

use App\Support\AdminPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PageViews
{
    public function get(Filters $filters, $hub = 'all')
    {
        $query = (new Queries)->pageViews($filters)->leftJoin('activity_pages as p', function ($join) {
            $join->on('p.id', '=', 'e.page_id')->on('p.visit_id', '=', 'e.visit_id')->on('p.platform', '=', 'e.platform');
        });
        $url = "COALESCE(NULLIF(e.url, ''), p.url)";
        $route = "COALESCE(NULLIF(e.route_name, ''), p.route_name)";
        $groups = $query->selectRaw("$route as route_name, $url as url, e.platform, COUNT(*) as total, MIN(e.occurred_at) as first_view")
            ->groupByRaw("$route, $url, e.platform")->cursor();
        $pages = []; $total = 0; $firstView = null;
        $hubs = collect(PageIdentity::HUBS)->map(function ($name, $key) { return (object) ['key' => $key, 'name' => $name, 'total' => 0]; });
        $focus = collect(['quizzes' => 'Quizzes', 'chord-finder' => 'Chord Finder', 'circle-of-fifths' => 'Circle of Fifths'])
            ->map(function ($name, $key) { return (object) ['key' => $key, 'name' => $name, 'total' => 0]; });
        foreach ($groups as $group) {
            $page = PageIdentity::describe($group);
            $count = (int) $group->total;
            $total += $count; $hubs[$page->hub]->total += $count;
            if (isset($focus[$page->section])) $focus[$page->section]->total += $count;
            if ($firstView === null || $group->first_view < $firstView) $firstView = $group->first_view;
            if (!isset($pages[$page->key])) { $page->total = 0; $pages[$page->key] = $page; }
            $pages[$page->key]->total += $count;
        }
        $pages = collect($pages)->filter(function ($page) use ($hub) { return $hub === 'all' || $page->hub === $hub; })
            ->sort(function ($a, $b) { return ($b->total <=> $a->total) ?: strcmp($a->key, $b->key); })->values();
        $current = LengthAwarePaginator::resolveCurrentPage('pages_page');
        $visible = $pages->slice(($current - 1) * AdminPagination::PER_PAGE, AdminPagination::PER_PAGE)->values();
        $this->titles($visible);
        $paginator = new LengthAwarePaginator($visible, $pages->count(), AdminPagination::PER_PAGE, $current,
            ['path' => LengthAwarePaginator::resolveCurrentPath(), 'pageName' => 'pages_page', 'fragment' => 'pageview-pages']);
        $paginator->withQueryString();
        return ['total' => $total, 'first_view' => $firstView, 'hubs' => $hubs->sortByDesc('total'), 'focus' => $focus,
            'selected_hub' => $hub, 'pages' => $paginator];
    }

    private function titles($pages)
    {
        // Current catalogue names are display labels only, never reconstructed
        // historical titles. Use scalar reads, without model appends/eager loads.
        foreach (['quiz' => ['quizzes', 'title', 'Quiz'], 'post' => ['posts', 'title', 'Blog article'],
            'course' => ['crash_courses', 'title', 'Crash Course'], 'infographic' => ['infographs', 'name', 'Infographic'],
            'pianist' => ['pianists', 'name', 'Pianist'], 'piece' => ['pieces', 'name', 'Piece']] as $kind => [$table, $column, $prefix]) {
            $items = $pages->where('content_type', $kind);
            if ($items->isEmpty()) continue;
            $keys = $items->pluck('content_key')->unique()->all();
            $query = DB::table($table)->whereIn('id', array_values(array_filter($keys, 'ctype_digit')));
            if ($kind !== 'piece') $query->orWhereIn('slug', $keys);
            $rows = $query->get($kind === 'piece' ? ['id', $column] : ['id', 'slug', $column]);
            foreach ($items as $page) {
                $row = $rows->first(function ($row) use ($page) { return ($row->slug ?? null) === $page->content_key || (string) $row->id === $page->content_key; });
                if ($row) $page->name = $prefix.' · '.rtrim(trim($row->$column), '. ');
            }
        }
    }
}
