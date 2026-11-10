<?php

namespace App\Services\Admin;

use App\Blog\{Post, Topic};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class PostsTable
{
    const SORTS = [
        'date_desc' => 'Date · newest', 'date_asc' => 'Date · oldest',
        'title_asc' => 'Title · A–Z', 'title_desc' => 'Title · Z–A',
        'reading_desc' => 'Reading time · longest', 'reading_asc' => 'Reading time · shortest',
        'published_desc' => 'Publication · newest', 'published_asc' => 'Publication · oldest',
    ];

    public function filters(Request $request): array
    {
        return AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => 'nullable|string|in:published,draft',
            'topic' => 'nullable|integer|min:1',
        ]) + ['status' => '', 'topic' => ''];
    }

    public function query(array $filters)
    {
        $query = Post::query();
        if ($filters['status'] === 'published') $query->whereNotNull('published_at');
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        if (!empty($filters['topic'])) $query->whereHas('topics', fn ($query) => $query->where('topics.id', $filters['topic']));
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($term, $like) {
                foreach (['title', 'description', 'content'] as $column) {
                    $query->orWhereRaw("LOWER(posts.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
                if (ctype_digit($term)) $query->orWhere('posts.id', $term);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['date' => 'created_at', 'title' => 'title', 'reading' => 'reading_time', 'published' => 'published_at'][$sort];
        return $query->orderBy('posts.'.$column, $direction)->orderBy('posts.id', $direction);
    }

    public function state(Request $request): array
    {
        return AdminTableFilters::query($this->filters(Request::create('/', 'GET', $request->query())));
    }

    public function modalUrl(Request $request, string $modal): string
    {
        return route('admin.posts.index', $this->state($request) + ['manage' => $modal]);
    }

    public function get(Request $request): array
    {
        $request->validate(['manage' => 'nullable|string|in:topics,audio']);
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters);
        $posts = $this->query($filters)->paginate($filters['per_page'])->appends(array_diff_key($returnFilters, ['page' => true]));
        $topics = Topic::orderBy('name')->orderBy('id')->get();
        $audio = app(BlogAudio::class)->all();
        return compact('filters', 'returnFilters', 'posts', 'topics', 'audio');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.posts.index', AdminTableFilters::query($filters));
    }
}
