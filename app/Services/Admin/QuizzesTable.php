<?php

namespace App\Services\Admin;

use App\Quiz\{Quiz, Topic, Level};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class QuizzesTable
{
    const SORTS = [
        'date_desc' => 'Date · newest', 'date_asc' => 'Date · oldest',
        'title_asc' => 'Title · A–Z', 'title_desc' => 'Title · Z–A',
        'published_desc' => 'Publication · newest', 'published_asc' => 'Publication · oldest',
    ];

    public function filters(Request $request): array
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => 'nullable|string|in:published,draft',
            'topic_id' => 'nullable|integer|exists:quiz_topics,id',
            'level' => 'nullable|integer|exists:quiz_levels,id',
        ]) + ['status' => '', 'topic_id' => '', 'level' => ''];
        foreach (['status', 'topic_id', 'level'] as $key) $filters[$key] = $filters[$key] ?? '';
        return $filters;
    }

    public function query(array $filters)
    {
        $query = Quiz::query();
        if ($filters['status'] === 'published') $query->whereNotNull('published_at');
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        if ($filters['topic_id'] !== '') $query->whereHas('topics', fn ($q) => $q->where('quiz_topics.id', $filters['topic_id']));
        if ($filters['level'] !== '') $query->where('level_id', $filters['level']);
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($q) use ($term, $like) {
                $q->whereRaw("LOWER(title) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(description) LIKE ? ESCAPE '!'", [$like]);
                if (ctype_digit($term)) $q->orWhere('quizzes.id', $term);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        return $query->orderBy(['date' => 'created_at', 'title' => 'title', 'published' => 'published_at'][$sort], $direction)
            ->orderBy('id', $direction);
    }

    public function get(Request $request): array
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $quizzes = $this->query($filters)->with(['topics', 'level'])->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $topics = Topic::orderBy('name')->orderBy('id')->get();
        $levels = Level::orderBy('id')->get();
        $selection = $request->validate(['manage' => 'nullable|string|in:audio,topics,images']);
        $manage = $selection['manage'] ?? null;
        $files = in_array($manage, ['audio', 'images'], true)
            ? app(QuizMedia::class)->files($request, $manage, $returnFilters) : null;
        return compact('quizzes', 'filters', 'returnFilters', 'topics', 'levels', 'manage', 'files');
    }

    public function url(Request $request, string $manage): string
    {
        $filters = $this->filters(Request::create('/', 'GET', $request->query()));
        $query = AdminTableFilters::query($filters) + ['manage' => $manage];
        if (in_array($manage, ['audio', 'images'], true)) {
            $data = $request->validate(['media_page' => 'nullable|integer|min:1|max:1000000']);
            if (!empty($data['media_page'])) $query['media_page'] = $data['media_page'];
        }
        return route('admin.quizzes.index', $query);
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.quizzes.index', AdminTableFilters::query($filters));
    }
}
