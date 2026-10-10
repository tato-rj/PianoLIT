<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, Tag};
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\{EngineManager, Engines\Engine};

class TagSearchSyncTest extends ReviewTestCase
{
    private $tag;
    private $piece;
    private $engine;

    public function setUp(): void
    {
        parent::setUp();
        $admin = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($admin, 'admin');
        $this->tag = create(Tag::class, ['name' => 'romantic', 'type' => 'period', 'creator_id' => $admin->id]);
        $this->piece = Model::withoutEvents(function () {
            return create(Piece::class, ['composer_id' => create(Composer::class)->id]);
        });
        $this->piece->tags()->attach($this->tag);

        $this->engine = \Mockery::mock(Engine::class);
        $manager = \Mockery::mock(EngineManager::class);
        $manager->shouldReceive('engine')->andReturn($this->engine);
        $this->app->instance(EngineManager::class, $manager);
        config(['scout.queue' => false]);
    }

    public function test_admin_ordering_changes_and_unchanged_saves_do_not_call_search_engine()
    {
        $this->engine->shouldNotReceive('update');

        foreach ([2, 5, null, null] as $ordering) {
            $this->patch(route('admin.tags.update', $this->tag), [
                'name' => 'romantic', 'type' => 'period', 'ordering' => $ordering,
            ])->assertRedirect()->assertSessionHasNoErrors();
            $this->assertSame($ordering, $this->tag->fresh()->ordering);
        }
    }

    public function test_admin_rename_with_ordering_still_reindexes_linked_pieces()
    {
        $this->engine->shouldReceive('update')->once()->withArgs(function ($pieces) {
            return $pieces->modelKeys() === [$this->piece->id]
                && $pieces->first()->toSearchableArray()['tags_array']->contains('baroque');
        });

        $this->patch(route('admin.tags.update', $this->tag), [
            'name' => 'baroque', 'type' => 'period', 'ordering' => 1,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('baroque', $this->tag->fresh()->name);
        $this->assertSame(1, $this->tag->fresh()->ordering);
    }

    public function test_tag_type_changes_still_reindex_linked_pieces()
    {
        $this->engine->shouldReceive('update')->once()->withArgs(function ($pieces) {
            return $pieces->modelKeys() === [$this->piece->id]
                && $pieces->first()->tags->first()->type === 'genre';
        });

        $this->patch(route('admin.tags.update', $this->tag), [
            'name' => 'romantic', 'type' => 'genre',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('genre', $this->tag->fresh()->type);
    }
}
