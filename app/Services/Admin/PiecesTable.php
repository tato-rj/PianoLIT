<?php

namespace App\Services\Admin;

use App\{Composer, Piece};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;

class PiecesTable
{
    const SORTS = [
        'updated_desc' => 'Updated · newest', 'updated_asc' => 'Updated · oldest',
        'id_desc' => 'ID · highest', 'id_asc' => 'ID · lowest',
        'name_asc' => 'Piece · A–Z', 'name_desc' => 'Piece · Z–A',
        'composer_asc' => 'Composer · A–Z', 'composer_desc' => 'Composer · Z–A',
        'tags_desc' => 'Tags · most', 'tags_asc' => 'Tags · fewest',
        'level_asc' => 'Level · easiest', 'level_desc' => 'Level · hardest',
        'highlight_desc' => 'Highlighted · newest', 'highlight_asc' => 'Highlighted · oldest',
        'favorites_desc' => 'Favorited · most', 'favorites_asc' => 'Favorited · fewest',
    ];

    public function filters(Request $request)
    {
        return AdminTableFilters::fromRequest($request, self::SORTS, 'updated_desc', [
            'without_videos' => 'sometimes|boolean',
            'without_moments' => 'sometimes|boolean',
            'without_synthesia' => 'sometimes|boolean',
            'creator_id' => 'sometimes|integer|min:1',
            'itunes' => 'sometimes|string|max:255',
            'videos' => 'sometimes|string|max:255',
            'score_path' => 'sometimes|string|max:255',
            'audio_path' => 'sometimes|string|max:255',
            'is_free' => 'sometimes|string|max:255',
        ]);
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $pieces = $this->query($filters)->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true]), 'updated_desc'));
        $returnFilters = AdminTableFilters::query($filters, 'updated_desc');

        return compact('pieces', 'filters', 'returnFilters');
    }

    public function query(array $filters)
    {
        // Override unrelated model counts; render loaded fields without serializing appends.
        $query = Piece::select('pieces.*')->withCount(['tags', 'favorites' => function ($query) {
            \App\Activity\ExcludedAccounts::owned($query, 'favorites.user_id');
        }])->with(['tags', 'composer' => function ($query) { $query->select('id', 'name', 'cover_path'); }]);
        $query->selectSub(\App\FreePick::select('scheduled_at')->whereColumn('piece_id', 'pieces.id')
            ->whereNull('retired_at')->where('scheduled_at', '>', now('UTC'))->orderBy('scheduled_at')->limit(1), 'next_freepick_at');
        foreach (['creator_id', 'itunes', 'videos', 'score_path', 'audio_path'] as $column) {
            if (!isset($filters[$column])) continue;
            if ($filters[$column] === 'missing') $query->whereNull('pieces.'.$column);
            else $query->where('pieces.'.$column, $filters[$column]);
        }
        $this->applyFilters($query, $filters);
        if (isset($filters['is_free'])) $query->free(filter_var($filters['is_free'], FILTER_VALIDATE_BOOLEAN));
        [$sort, $direction] = explode('_', $filters['sort']);
        $columns = ['id' => 0, 'name' => 1, 'composer' => 2, 'tags' => 3, 'level' => 4, 'highlight' => 5, 'favorites' => 6];
        if ($sort === 'updated') $query->orderBy('pieces.updated_at', $direction);
        else $this->orderColumn($query, $columns[$sort], $direction);

        return $query->orderByDesc('pieces.id');
    }

    public function redirectAfterDeletion(array $filters)
    {
        $lastPage = max(1, (int) ceil($this->query($filters)->count() / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);

        return redirect()->route('admin.pieces.index', AdminTableFilters::query($filters, 'updated_desc'));
    }

    public function response(Request $request)
    {
        $request->validate([
            'draw' => 'sometimes|integer|min:0',
            'start' => 'sometimes|integer|min:0|max:10000000',
            'length' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|array',
            'search.value' => 'nullable|string|max:255',
            'order' => 'sometimes|array|max:8',
            'order.*.column' => 'required|integer|min:0|max:7',
            'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:8',
            'columns.*' => 'required|array',
            'columns.*.data' => 'nullable|string|max:100',
            'columns.*.name' => 'nullable|string|max:100',
            'columns.*.search' => 'sometimes|array',
            'columns.*.search.value' => 'nullable|string|max:255',
            'without_videos' => 'sometimes|boolean',
            'without_moments' => 'sometimes|boolean',
            'without_synthesia' => 'sometimes|boolean',
            'creator_id' => 'sometimes|integer|min:1',
            'itunes' => 'sometimes|string|max:255',
            'videos' => 'sometimes|string|max:255',
            'score_path' => 'sometimes|string|max:255',
            'audio_path' => 'sometimes|string|max:255',
            'is_free' => 'sometimes|string|max:255',
        ]);

        // Override the model's default counts: the table needs only these two.
        $query = Piece::select('pieces.*')->withCount(['tags', 'favorites' => function ($query) {
            \App\Activity\ExcludedAccounts::owned($query, 'favorites.user_id');
        }])
            ->with(['tags', 'composer' => function ($query) { $query->select('id', 'name'); }])
            ->filters(['creator_id', 'itunes', 'videos', 'score_path', 'audio_path']);
        $query->selectSub(\App\FreePick::select('scheduled_at')->whereColumn('piece_id', 'pieces.id')
            ->whereNull('retired_at')->where('scheduled_at', '>', now('UTC'))->orderBy('scheduled_at')->limit(1), 'next_freepick_at');
        if ($request->filled('is_free')) $query->free($request->boolean('is_free'));

        $table = (new EloquentDataTable($query))
            // Hide appends and relations BEFORE serialization, so no media/history
            // queries run for unused data. Templates still use the loaded models.
            ->makeHidden((new Piece)->adminTableHiddenAttributes())
            ->only(['id', 'name', 'composer', 'tags', 'level', 'ranking', 'favorited', 'actions'])
            ->whitelist([])
            ->addColumn('composer', function ($piece) { return ['short_name' => $piece->composer->short_name]; })
            ->filter(function ($query) use ($request) {
                $this->applyFilters($query, array_merge($request->only(['without_videos', 'without_moments', 'without_synthesia']), ['search' => trim((string) $request->input('search.value', ''))]));
            })
            ->order(function ($query) use ($request) {
                $ordered = false;
                foreach ($request->input('order', []) as $order) {
                    $direction = $order['dir'];
                    if (!$this->orderColumn($query, (int) $order['column'], $direction)) continue;
                    $ordered = true;
                }
                if (! $ordered) $query->orderBy('pieces.updated_at', 'desc');
                $query->orderBy('pieces.id', 'desc');
            });

        foreach (['name', 'tags', 'level', 'ranking', 'favorited', 'actions'] as $column) {
            $table->addColumn($column, function ($item) use ($column) {
                return view('admin.pages.pieces.table.'.$column, compact('item'))->render();
            });
        }

        return $table->rawColumns(['name', 'tags', 'level', 'ranking', 'favorited', 'actions'])->make(true);
    }

    protected function applyFilters($query, array $filters)
    {
        $performance = function ($videos) {
            $videos->where(function ($videos) {
                $videos->where('type', 'Performance')->orWhere('category', 'performance');
            });
        };
        if (!empty($filters['without_videos'])) $query->whereDoesntHave('tutorials', $performance);
        if (!empty($filters['without_moments'])) {
            $query->whereHas('tutorials', $performance)
                ->whereDoesntHave('tutorials', function ($videos) use ($performance) {
                    $performance($videos);
                    $videos->whereHas('moments');
                });
        }
        if (!empty($filters['without_synthesia'])) {
            $query->whereDoesntHave('tutorials', function ($videos) {
                $videos->where(function ($videos) {
                    $videos->where('type', 'like', '%synthesia%')->orWhere('category', 'synthesia');
                });
            });
        }
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $query->where(function ($query) use ($like) {
                foreach (['id', 'name', 'nickname', 'key', 'collection_name', 'collection_number', 'catalogue_name', 'catalogue_number', 'movement_number'] as $column) {
                    $query->orWhereRaw("LOWER(pieces.{$column}) LIKE ? ESCAPE '!'", [$like]);
                }
                $query->orWhereHas('composer', function ($composer) use ($like) {
                    $composer->whereRaw("LOWER(composers.name) LIKE ? ESCAPE '!'", [$like]);
                })->orWhereHas('tags', function ($tags) use ($like) {
                    $tags->whereRaw("LOWER(tags.name) LIKE ? ESCAPE '!'", [$like]);
                });
            });
        }
    }

    protected function orderColumn($query, int $column, string $direction)
    {
        // Fixed indices prevent client-supplied column names becoming SQL.
        switch ($column) {
            case 0: $query->orderBy('pieces.id', $direction); break;
            case 1: $query->orderBy('pieces.name', $direction); break;
            case 2:
                $query->orderBy(Composer::select('name')->whereColumn('composers.id', 'pieces.composer_id'), $direction);
                break;
            case 3: $query->orderBy('tags_count', $direction); break;
            case 4:
                // Sort difficulty, including early/late variants, rather than
                // the HTML badge or alphabetically by level name.
                $query->orderBy(DB::table('tags')->join('piece_tag', 'tags.id', '=', 'piece_tag.tag_id')
                    ->selectRaw("CASE WHEN LOWER(tags.name) LIKE '%elementary%' THEN 1 WHEN LOWER(tags.name) LIKE '%beginner%' THEN 2 WHEN LOWER(tags.name) LIKE '%intermediate%' THEN 3 WHEN LOWER(tags.name) LIKE '%advanced%' THEN 4 ELSE 5 END + CASE WHEN LOWER(tags.name) LIKE 'early %' THEN -0.25 WHEN LOWER(tags.name) LIKE 'late %' THEN 0.25 ELSE 0 END")
                    ->whereColumn('piece_tag.piece_id', 'pieces.id')->whereIn('tags.type', ['level', 'sublevel'])
                    ->orderByRaw("CASE WHEN tags.type = 'sublevel' THEN 0 ELSE 1 END")->orderBy('tags.id')->limit(1), $direction);
                break;
            // This existing column displays the highlight date.
            case 5: $query->orderBy('pieces.highlighted_at', $direction); break;
            case 6: $query->orderBy('favorites_count', $direction); break;
            default: return false;
        }
        return true;
    }
}
