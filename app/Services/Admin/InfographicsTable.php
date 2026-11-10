<?php

namespace App\Services\Admin;

use App\Infograph\{Infograph, Topic};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class InfographicsTable
{
    const SORTS = [
        'date_desc' => 'Date · newest', 'date_asc' => 'Date · oldest',
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'downloads_desc' => 'Downloads · most', 'downloads_asc' => 'Downloads · fewest',
        'score_desc' => 'Score · highest', 'score_asc' => 'Score · lowest',
    ];

    public function filters(Request $request): array
    {
        return AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'status' => 'nullable|string|in:published,draft',
            'topic_id' => 'nullable|integer|min:1|exists:infograph_topics,id',
            'gift' => 'nullable|string|in:yes,no',
        ]) + ['status' => '', 'topic_id' => '', 'gift' => ''];
    }

    public function query(array $filters)
    {
        $query = Infograph::query();
        if ($filters['status'] === 'published') $query->whereNotNull('published_at');
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        if ($filters['gift'] === 'yes') $query->whereNotNull('giftable_at');
        if ($filters['gift'] === 'no') $query->whereNull('giftable_at');
        if ($filters['topic_id']) $query->whereHas('topics', fn ($query) => $query->where('infograph_topics.id', $filters['topic_id']));
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($term, $like) {
                $query->whereRaw("LOWER(infographs.name) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("LOWER(infographs.description) LIKE ? ESCAPE '!'", [$like])
                    ->orWhereHas('topics', fn ($query) => $query->whereRaw("LOWER(infograph_topics.name) LIKE ? ESCAPE '!'", [$like]));
                if (ctype_digit($term)) $query->orWhere('infographs.id', $term);
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['date' => 'created_at', 'name' => 'name', 'downloads' => 'downloads', 'score' => 'score'][$sort];
        return $query->orderBy('infographs.'.$column, $direction)->orderBy('infographs.id', $direction);
    }

    public function get(Request $request): array
    {
        $filters = $this->filters($request);
        $request->validate(['topics' => 'nullable|string|in:1', 'create' => 'nullable|string|in:1']);
        $returnFilters = AdminTableFilters::query($filters);
        $infographs = $this->query($filters)->with('topics')->paginate($filters['per_page'])
            ->appends(array_diff_key($returnFilters, ['page' => true]));
        $topics = Topic::ordered()->orderBy('id')->get();
        return compact('filters', 'returnFilters', 'infographs', 'topics');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.infographs.index', AdminTableFilters::query($filters));
    }
}
