<?php

namespace App\Services\Admin;

use App\{Composer, Piece, Tag};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlaylistPieceFinder extends PiecesTable
{
    const SORTS = [
        'updated_desc' => 'Updated · newest', 'name_asc' => 'Piece · A–Z', 'name_desc' => 'Piece · Z–A',
        'composer_asc' => 'Composer · A–Z', 'composer_desc' => 'Composer · Z–A',
        'level_asc' => 'Level · easiest', 'level_desc' => 'Level · hardest',
    ];

    public function filters(Request $request)
    {
        return array_merge(['composer_id' => '', 'level_id' => ''], AdminTableFilters::fromRequest($request, self::SORTS, 'updated_desc', [
            'composer_id' => 'nullable|integer|exists:composers,id',
            'level_id' => ['nullable', 'integer', Rule::exists('tags', 'id')->whereIn('type', ['level', 'sublevel'])],
        ]));
    }

    public function query(array $filters)
    {
        // Select resets the Piece model's unrelated default aggregate subqueries.
        $query = Piece::select('pieces.*')->with(['tags', 'composer' => function ($query) { $query->without('country')->select('id', 'name', 'cover_path'); }]);
        $this->applyFilters($query, $filters);
        if (!empty($filters['composer_id'])) $query->where('pieces.composer_id', $filters['composer_id']);
        if (!empty($filters['level_id'])) $query->whereHas('tags', function ($tags) use ($filters) { $tags->where('tags.id', $filters['level_id']); });
        [$sort, $direction] = explode('_', $filters['sort']);
        if ($sort === 'updated') $query->orderBy('pieces.updated_at', $direction);
        else $this->orderColumn($query, ['name' => 1, 'composer' => 2, 'level' => 4][$sort], $direction);
        return $query->orderByDesc('pieces.id');
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $pieces = $this->query($filters)->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)->withPath($request->url())
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true]), 'updated_desc'));
        return compact('filters', 'pieces');
    }

    public function choices()
    {
        return [
            'composers' => Composer::has('pieces')->orderBy('name')->pluck('name', 'id'),
            'levels' => Tag::whereIn('type', ['level', 'sublevel'])->orderBy('id')->pluck('name', 'id')->map(function ($name) { return ucfirst($name); }),
        ];
    }
}
