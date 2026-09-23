<?php

namespace Tests\Review;

use App\{Piece, Tag, User};
use App\Api\Api;
use App\Services\RecentlyViewedPieces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Redis};

class RecentlyViewedPiecesTest extends ReviewTestCase
{
    protected $user, $first, $second;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        Cache::put('app.discover', collect([
            ['title' => 'Free weekly pick', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
            ['title' => 'Composers', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
            ['title' => 'Latest pieces', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
        ]), 60);

        Model::withoutEvents(function () {
            $this->user = create(User::class)->setAppends(['full_name']);
            $this->first = create(Piece::class, ['name' => 'First viewed piece']);
            $this->second = create(Piece::class, ['name' => 'Second viewed piece', 'composer_id' => $this->first->composer_id]);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short'] as $type => $name) {
                $tag = create(Tag::class, compact('type', 'name'));
                $this->first->tags()->attach($tag);
                $this->second->tags()->attach($tag);
            }
        });
    }

    public function test_piece_visits_create_a_unique_newest_first_row_above_latest_pieces()
    {
        $this->actingAs($this->user, 'web');
        $this->get(route('webapp.discover'))->assertOk()->assertDontSee('Recently viewed');
        $this->get(route('webapp.pieces.show', $this->first))->assertOk();
        $this->get(route('webapp.discover'))->assertOk()->assertSeeInOrder(['Recently viewed', 'First viewed piece', 'Latest pieces']);
        $this->travel(1)->seconds();
        $this->get(route('webapp.pieces.show', $this->second))->assertOk();
        $this->travel(1)->seconds();
        $this->get(route('webapp.pieces.show', $this->first))->assertOk();

        $response = $this->get(route('webapp.discover'))->assertOk()
            ->assertSeeInOrder(['Recently viewed', 'First viewed piece', 'Second viewed piece', 'Latest pieces']);
        $row = collect($response->viewData('rows'))->firstWhere('title', 'Recently viewed');
        $this->assertSame([$this->first->id, $this->second->id], $row['content']->pluck('id')->all());
        $this->assertDatabaseCount('recently_viewed_pieces', 2);
        $this->assertFalse(Cache::get('app.discover')->contains('title', 'Recently viewed'));
        $this->assertFalse((new Api)->discover()->contains('title', 'Recently viewed'));
    }

    public function test_history_is_private_to_the_signed_in_account_and_guests_are_not_recorded()
    {
        (new RecentlyViewedPieces)->record($this->user, $this->first);
        $this->get(route('webapp.discover', ['user_id' => $this->user->id]))->assertOk()->assertDontSee('Recently viewed');
        $this->get(route('webapp.pieces.show', [$this->second, 'user_id' => $this->user->id]))->assertOk();
        $this->assertDatabaseCount('recently_viewed_pieces', 1);

        $other = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($other, 'web');
        $this->get(route('webapp.discover', ['user_id' => $this->user->id]))->assertOk()->assertDontSee('Recently viewed');
        $this->get(route('webapp.pieces.show', [$this->second, 'user_id' => $this->user->id]))->assertOk();
        $this->assertDatabaseHas('recently_viewed_pieces', ['user_id' => $other->id, 'piece_id' => $this->second->id]);
        $this->assertDatabaseMissing('recently_viewed_pieces', ['user_id' => $this->user->id, 'piece_id' => $this->second->id]);
        $this->assertSame([$this->second->id], (new RecentlyViewedPieces)->forUser($other)->pluck('id')->all());
    }

    public function test_only_opening_the_piece_page_counts_as_a_visit()
    {
        $this->actingAs($this->user, 'web');
        $this->get(route('webapp.pieces.composer', $this->first))->assertOk();
        $this->call('HEAD', route('webapp.pieces.show', $this->first))->assertOk();
        $this->withExceptionHandling()->get(route('webapp.pieces.show', 'missing-piece'))->assertNotFound();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_history_is_limited_to_twelve_cards_and_deleted_records_are_cleaned_up()
    {
        $history = new RecentlyViewedPieces;
        $pieces = Model::withoutEvents(function () {
            return create(Piece::class, ['composer_id' => $this->first->composer_id], 13);
        });
        foreach ($pieces as $piece) {
            $history->record($this->user, $piece);
            $this->travel(1)->seconds();
        }
        $this->assertSame($pieces->reverse()->take(12)->pluck('id')->all(), $history->forUser($this->user)->pluck('id')->all());
        DB::table('pieces')->where('id', $pieces->last()->id)->delete();
        $this->assertDatabaseMissing('recently_viewed_pieces', ['piece_id' => $pieces->last()->id]);
        $this->assertCount(12, $history->forUser($this->user));
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }
}
