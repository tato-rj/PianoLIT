<?php

namespace App\Services\Admin;

use App\Playlist;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class PlaylistsTable
{
    const SORTS = [
        'order_asc' => 'Display order · first to last', 'order_desc' => 'Display order · last to first',
        'name_asc' => 'Name · A–Z', 'name_desc' => 'Name · Z–A',
        'pieces_desc' => 'Pieces · most', 'pieces_asc' => 'Pieces · fewest',
        'updated_desc' => 'Updated · newest', 'updated_asc' => 'Updated · oldest',
    ];

    public function filters(Request $request)
    {
        $filters = AdminTableFilters::fromRequest($request, self::SORTS, 'order_asc', [
            'group' => 'nullable|string|in:general,journey',
            'status' => 'nullable|string|in:draft,published,scheduled',
        ]);
        unset($filters['group']);
        return array_merge($filters, ['status' => $filters['status'] ?? '']);
    }

    public function query(array $filters)
    {
        $query = Playlist::byGroup(null)
            ->withCount(['pieces as playable_pieces_count' => function ($pieces) { $pieces->has('tutorials'); }]);
        if ($filters['status'] === 'draft') $query->whereNull('published_at');
        if ($filters['status'] === 'published') $query->published();
        if ($filters['status'] === 'scheduled') $query->where('published_at', '>', now());
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                foreach (['name', 'subtitle', 'description', 'featured'] as $column) {
                    $query->orWhereRaw("LOWER(playlists.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
            });
        }
        [$sort, $direction] = explode('_', $filters['sort']);
        $column = ['order' => 'playlists.order', 'name' => 'playlists.name', 'pieces' => 'pieces_count', 'updated' => 'playlists.updated_at'][$sort];
        return $query->orderBy($column, $direction)->orderBy('playlists.id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query($filters, 'order_asc');
        $playlists = $this->query($filters)->paginate($filters['per_page'])->appends(array_diff_key($returnFilters, ['page' => true]));
        return compact('filters', 'returnFilters', 'playlists');
    }

    public function redirect(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);
        return redirect()->route('admin.playlists.index', AdminTableFilters::query($filters, 'order_asc'));
    }
}
