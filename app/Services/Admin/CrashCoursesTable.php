<?php

namespace App\Services\Admin;

use App\CrashCourse\{CrashCourse, CrashCourseTopic};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class CrashCoursesTable
{
    const SORTS = [
        'date_desc' => 'Date · newest', 'date_asc' => 'Date · oldest',
        'title_asc' => 'Title · A–Z', 'title_desc' => 'Title · Z–A',
        'lessons_desc' => 'Lessons · most', 'lessons_asc' => 'Lessons · fewest',
        'active_desc' => 'Active subscriptions · most', 'active_asc' => 'Active subscriptions · fewest',
        'published_desc' => 'Publication · newest', 'published_asc' => 'Publication · oldest',
    ];

    public function filters(Request $request): array
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => 'nullable|string|in:published,draft',
            'topic_id' => 'nullable|integer|exists:crash_course_topics,id',
        ]) + ['status' => '', 'topic_id' => ''];
        foreach (['status', 'topic_id'] as $key) $filters[$key] = $filters[$key] ?? '';
        return $filters;
    }

    public function query(array $filters)
    {
        $query = CrashCourse::query()->withCount([
            'subscriptions as directory_active_subscriptions_count' => fn ($q) => $q->whereNull('completed_at')->whereNull('cancelled_at'),
        ]);
        if ($filters['status'] === 'published') $query->whereNotNull('published_at');
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        if ($filters['topic_id'] !== '') $query->whereHas('topics', fn ($q) => $q->where('crash_course_topics.id', $filters['topic_id']));
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($q) use ($term, $like) {
                $q->whereRaw("LOWER(crash_courses.title) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(crash_courses.description) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('topics', fn ($topics) => $topics->whereRaw("LOWER(crash_course_topics.name) LIKE ? ESCAPE '!'", [$like]));
                if (ctype_digit($term)) $q->orWhere('crash_courses.id', $term);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = [
            'date' => 'crash_courses.created_at', 'title' => 'crash_courses.title',
            'lessons' => 'lessons_count', 'active' => 'directory_active_subscriptions_count',
            'published' => 'crash_courses.published_at',
        ][$sort];
        return $query->orderBy($column, $direction)->orderBy('crash_courses.id', $direction);
    }

    public function state(Request $request): array
    {
        return AdminTableFilters::query($this->filters(Request::create('/', 'GET', $request->query())));
    }

    public function modalUrl(Request $request): string
    {
        return route('admin.crashcourses.index', $this->state($request) + ['manage' => 'topics']);
    }

    public function get(Request $request): array
    {
        $request->validate(['manage' => 'nullable|string|in:topics']);
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $crashcourses = $this->query($filters)->with('topics')->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $topics = CrashCourseTopic::orderBy('name')->orderBy('id')->get();
        return compact('filters', 'returnFilters', 'crashcourses', 'topics');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.crashcourses.index', AdminTableFilters::query($filters));
    }
}
