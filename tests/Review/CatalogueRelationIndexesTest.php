<?php

namespace Tests\Review;

use App\{Composer, Piece, Tag};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CatalogueRelationIndexesTest extends ReviewTestCase
{
    private function migration()
    {
        require_once database_path('migrations/2026_10_10_140000_add_catalogue_relation_indexes.php');
        return new \AddCatalogueRelationIndexes;
    }

    private function relationQueries()
    {
        return [
            'piece' => Piece::query()->setEagerLoads([])->orderBy('pieces.id'),
            'composer' => Composer::query()->setEagerLoads([])->orderBy('composers.id'),
            'tag' => Tag::query()->withCount('pieces')->orderBy('tags.id'),
            'performance' => Piece::query()->select('pieces.id')->setEagerLoads([])
                ->withExists(['performances' => function ($query) { $query->approved(); }])->orderBy('pieces.id'),
        ];
    }

    private function plans()
    {
        return collect($this->relationQueries())->map(function ($query) {
            return collect(DB::select('EXPLAIN QUERY PLAN '.$query->toSql(), $query->getBindings()))
                ->pluck('detail')->implode("\n");
        });
    }

    public function test_relation_counts_and_badges_use_index_lookups_instead_of_repeated_table_scans()
    {
        $migration = $this->migration();
        $migration->down();
        $before = $this->plans();
        $relations = [
            'piece_views' => 'piece', 'favorites' => 'piece', 'tutorials' => 'piece',
            'pieces' => 'composer', 'piece_tag' => 'tag', 'performances' => 'performance',
        ];
        foreach ($relations as $table => $query) {
            $this->assertStringContainsString('SCAN '.$table, $before[$query]);
        }

        $migration->up();
        $after = $this->plans();
        foreach ($relations as $table => $query) {
            $this->assertStringContainsString('SEARCH '.$table.' USING', $after[$query]);
            $this->assertStringNotContainsString('SCAN '.$table, $after[$query]);
        }
    }

    public function test_indexes_are_non_unique_and_rollback_preserves_rows_and_relation_results()
    {
        Model::withoutEvents(function () {
            $composer = create(Composer::class);
            $pieces = collect([
                create(Piece::class, ['composer_id' => $composer->id]),
                create(Piece::class, ['composer_id' => $composer->id]),
            ]);
            $tag = create(Tag::class, ['type' => 'mood', 'name' => 'calm']);
            $tag->pieces()->attach($pieces->pluck('id'));
            foreach ([1, 2] as $userId) {
                DB::table('piece_views')->insert(['piece_id' => $pieces[0]->id, 'user_id' => $userId]);
                DB::table('favorites')->insert(['piece_id' => $pieces[0]->id, 'user_id' => $userId]);
                DB::table('tutorials')->insert([
                    'piece_id' => $pieces[0]->id, 'type' => 'Performance', 'category' => 'performance', 'filename' => 'example',
                ]);
                DB::table('performances')->insert([
                    'piece_id' => $pieces[0]->id, 'user_id' => $userId, 'approved_at' => now(),
                ]);
            }
        });

        $snapshot = function () {
            return collect($this->relationQueries())->map(function ($query) {
                return $query->get()->map(function ($model) { return $model->getAttributes(); })->all();
            })->all();
        };
        $indexed = $snapshot();
        foreach (['views_count', 'favorites_count', 'tutorials_count'] as $count) {
            $this->assertSame([2, 0], array_column($indexed['piece'], $count));
        }
        $this->assertSame([2], array_column($indexed['composer'], 'pieces_count'));
        $this->assertSame([2], array_column($indexed['tag'], 'pieces_count'));
        $this->assertSame([1, 0], array_column($indexed['performance'], 'performances_exists'));

        $indexes = [
            'piece_views' => ['piece_id'], 'favorites' => ['piece_id'],
            'tutorials' => ['piece_id'], 'performances' => ['piece_id'],
            'pieces' => ['composer_id'], 'piece_tag' => ['tag_id', 'piece_id'],
        ];
        $assertIndexes = function ($present) use ($indexes) {
            foreach ($indexes as $table => $columns) {
                $name = $table.'_'.implode('_', $columns).'_index';
                $index = collect(DB::select('PRAGMA index_list("'.$table.'")'))->firstWhere('name', $name);
                if (!$present) {
                    $this->assertNull($index);
                    continue;
                }
                $this->assertNotNull($index);
                $this->assertSame(0, $index->unique);
                $this->assertSame($columns, collect(DB::select('PRAGMA index_info("'.$name.'")'))->pluck('name')->all());
            }
        };
        $assertIndexes(true);
        $migration = $this->migration();
        $migration->down();
        $assertIndexes(false);
        $this->assertSame($indexed, $snapshot());
        // The original pivot primary key and user-scoped favorites constraint remain.
        $this->assertNotEmpty(DB::select('PRAGMA index_list("piece_tag")'));
        $this->assertNotEmpty(DB::select('PRAGMA index_list("favorites")'));
        $migration->up();
        $assertIndexes(true);
        $this->assertSame($indexed, $snapshot());
    }
}
