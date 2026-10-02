<?php

namespace App\Services\Admin;

use App\{Composer, Piece};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\EloquentDataTable;

class PiecesTable
{
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
            'with_videos' => 'sometimes|boolean',
            'with_sections' => 'sometimes|boolean',
            'with_synthesia' => 'sometimes|boolean',
            'creator_id' => 'sometimes|integer|min:1',
            'itunes' => 'sometimes|string|max:255',
            'videos' => 'sometimes|string|max:255',
            'score_path' => 'sometimes|string|max:255',
            'audio_path' => 'sometimes|string|max:255',
            'is_free' => 'sometimes|string|max:255',
        ]);

        // Override the model's default counts: the table needs only these two.
        $query = Piece::select('pieces.*')->withCount(['tags', 'favorites'])
            ->with(['tags', 'composer' => function ($query) { $query->select('id', 'name'); }])
            ->filters(['creator_id', 'itunes', 'videos', 'score_path', 'audio_path', 'is_free']);

        $table = (new EloquentDataTable($query))
            // Hide appends and relations BEFORE serialization, so no media/history
            // queries run for unused data. Templates still use the loaded models.
            ->makeHidden((new Piece)->adminTableHiddenAttributes())
            ->only(['id', 'name', 'composer', 'tags', 'level', 'ranking', 'favorited', 'actions'])
            ->whitelist([])
            ->addColumn('composer', function ($piece) { return ['short_name' => $piece->composer->short_name]; })
            ->filter(function ($query) use ($request) {
                if ($request->boolean('with_videos', true)) $query->whereHas('tutorials');
                if ($request->boolean('with_sections', true)) $query->whereHas('tutorials.moments');
                if ($request->boolean('with_synthesia', true)) {
                    $query->whereHas('tutorials', function ($videos) {
                        $videos->where(function ($videos) {
                            $videos->where('type', 'like', '%synthesia%')->orWhere('category', 'synthesia');
                        });
                    });
                }
                foreach (preg_split('/\s+/u', trim((string) $request->input('search.value', ''))) as $term) {
                    if ($term === '') continue;
                    $query->where(function ($query) use ($term) {
                        foreach (['id', 'name', 'nickname', 'key', 'collection_name', 'collection_number', 'catalogue_name', 'catalogue_number', 'movement_number'] as $column) {
                            $query->orWhere('pieces.'.$column, 'like', '%'.$term.'%');
                        }
                        $query->orWhereHas('composer', function ($composer) use ($term) {
                            $composer->where('name', 'like', '%'.$term.'%');
                        })->orWhereHas('tags', function ($tags) use ($term) {
                            $tags->where('name', 'like', '%'.$term.'%');
                        });
                    });
                }
            })
            ->order(function ($query) use ($request) {
                $ordered = false;
                foreach ($request->input('order', []) as $order) {
                    $direction = $order['dir'];
                    // Fixed indices prevent client-supplied column names becoming SQL.
                    switch ((int) $order['column']) {
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
                        default: continue 2;
                    }
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
}
