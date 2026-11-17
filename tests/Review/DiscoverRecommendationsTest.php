<?php

namespace Tests\Review;

use App\{Composer, FavoriteFolder, Piece, Tag, User};
use App\Services\Discover\Recommendations;
use App\Services\RecentlyViewedPieces;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};
use Illuminate\Support\Str;

class DiscoverRecommendationsTest extends ReviewTestCase
{
    private $user, $composer, $tags = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-11-17 12:00:00'));
        Redis::shouldReceive('get')->andReturn(null);
        Model::withoutEvents(function () {
            $this->user = create(User::class);
            $this->composer = create(Composer::class);
            foreach (['calm' => 'mood', 'lively' => 'mood', 'waltz' => 'genre', 'staccato' => 'technique',
                'advanced' => 'level', 'beginner' => 'level'] as $name => $type) {
                $this->tags[$name] = create(Tag::class, compact('name', 'type'))->id;
            }
        });
    }

    private function piece(array $tags = [], array $fields = []): Piece
    {
        return Model::withoutEvents(function () use ($tags, $fields) {
            $piece = create(Piece::class, $fields + ['composer_id' => $this->composer->id, 'audio_path' => 'example.mp3',
                'collection_name' => null, 'highlighted_at' => null]);
            $piece->tags()->attach(array_map(fn ($name) => $this->tags[$name], $tags));
            return $piece;
        });
    }

    private function ids(?User $user = null): array
    {
        $user = $user ?: $this->user;
        $rows = app(Recommendations::class)->forUser($user, app(RecentlyViewedPieces::class)->forUser($user), Recommendations::playingLevel($user));
        return $rows['pieces']->pluck('id')->all();
    }

    private function mark(Piece $piece, string $marks = '[{}]')
    {
        DB::table('score_annotations')->insert(['user_id' => $this->user->id, 'piece_id' => $piece->id, 'score_key' => str_repeat('a', 64),
            'marks' => $marks, 'updated_at' => now(), 'created_at' => now()]);
    }

    private function event(Piece $piece, string $type, array $metadata, array $fields = [])
    {
        DB::table('activity_events')->insert($fields + ['user_id' => $this->user->id, 'event_id' => (string) Str::uuid(), 'page_id' => (string) Str::uuid(),
            'platform' => 'webapp', 'source' => 'browser', 'type' => $type, 'resource_type' => 'piece', 'resource_id' => (string) $piece->id,
            'metadata' => json_encode($metadata), 'occurred_at' => now(), 'occurred_at_utc' => now('UTC'), 'received_at' => now()]);
    }

    public function test_recent_opens_reorder_unseen_playable_pieces_and_a_distinct_save_outweighs_a_view()
    {
        $calm = $this->piece(['calm']);
        $lively = $this->piece(['lively']);
        $calmMatch = $this->piece(['calm']);
        $livelyMatch = $this->piece(['lively']);
        $highlight = $this->piece([], ['highlighted_at' => now()->subWeek()]);
        $empty = $this->piece(['calm'], ['audio_path' => null]);
        $this->assertSame($highlight->id, $this->ids()[0]);
        $history = app(RecentlyViewedPieces::class);
        $history->record($this->user, $calm);
        $viewed = $this->ids();
        $this->assertSame($calmMatch->id, $viewed[0]);
        $this->assertNotContains($calm->id, $viewed);
        $this->assertNotContains($empty->id, $viewed);
        $this->user->favorites()->attach($lively);
        $saved = $this->ids();
        $this->assertSame([$livelyMatch->id, $calmMatch->id], array_slice($saved, 0, 2));
        $folder = Model::withoutEvents(fn () => create(FavoriteFolder::class, ['user_id' => $this->user->id]));
        $this->user->favorites()->attach($lively, ['favorite_folder_id' => $folder->id]);
        $this->assertSame($saved, $this->ids());
        $this->assertNotContains($lively->id, $saved);
        $other = Model::withoutEvents(fn () => create(User::class));
        $foreignFolder = Model::withoutEvents(fn () => create(FavoriteFolder::class, ['user_id' => $other->id]));
        $this->user->favorites()->attach($highlight, ['favorite_folder_id' => $foreignFolder->id]);
        $this->assertSame($saved, $this->ids());
        $this->assertSame($highlight->id, $this->ids($other)[0]);
    }

    public function test_dated_views_fade_and_returning_updates_interest_without_voting_twice()
    {
        $old = $this->piece(['calm']);
        $new = $this->piece(['lively']);
        $calm = $this->piece(['calm']);
        $lively = $this->piece(['lively']);
        $history = app(RecentlyViewedPieces::class);
        $history->record($this->user, $old);
        $this->travel(20)->days();
        $history->record($this->user, $new);
        $this->assertSame($lively->id, $this->ids()[0]);
        $this->travel(4)->days();
        $history->record($this->user, $old);
        $this->assertSame($calm->id, $this->ids()[0]);
        $this->assertDatabaseCount('recently_viewed_pieces', 2);
    }

    /** @dataProvider mediaKinds */
    public function test_sustained_playback_changes_taste_and_skips_previews_repeats_and_unverified_activity($kind, $player)
    {
        $source = $this->piece(['waltz', 'advanced']);
        $match = $this->piece(['waltz', 'beginner']);
        $other = $this->piece(['staccato', 'advanced'], ['highlighted_at' => now()->subWeek()]);
        $metadata = ['media_kind' => $kind, 'media_id' => $player, 'played_seconds' => 30, 'unique_watched_seconds' => 22];
        $preview = array_merge($metadata, ['unique_watched_seconds' => 10]);
        $this->event($source, 'media_progress', $preview);
        $this->event($source, 'media_progress', $preview);
        $this->event($source, 'media_pause', $metadata, ['platform' => 'app']);
        $this->event($source, 'media_pause', $metadata, ['user_id' => $this->user->id + 100]);
        $this->assertSame($other->id, $this->ids()[0]);
        $this->event($source, 'media_progress', $metadata);
        $this->event($source, 'media_end', $metadata);
        $this->travel(6)->minutes();
        $ids = $this->ids();
        $this->assertSame($match->id, $ids[0]);
        $this->assertNotContains($source->id, $ids);
    }

    public function mediaKinds()
    {
        return [['video', 'performance'], ['video', 'synthesia'], ['audio', 'recording']];
    }

    public function test_markings_and_optional_level_change_rank_and_a_cleared_preference_restores_the_order()
    {
        $practice = $this->piece(['waltz', 'advanced']);
        $advanced = $this->piece(['waltz', 'advanced']);
        $beginner = $this->piece(['waltz', 'beginner']);
        $emptyMarks = $this->piece(['lively', 'beginner']);
        $this->mark($practice);
        $this->mark($emptyMarks, '[]');
        $ids = $this->ids();
        $this->assertSame($advanced->id, $ids[0]);
        $this->assertNotContains($practice->id, $ids);
        $this->assertContains($emptyMarks->id, $ids);
        $this->actingAs($this->user, 'web');
        $url = route('webapp.discover.level');
        $this->post($url, ['playing_level' => 'beginner', 'user_id' => $this->user->id + 1])->assertRedirect(route('webapp.discover'));
        $this->assertSame($beginner->id, $this->ids()[0]);
        $this->withExceptionHandling()->post($url, ['playing_level' => 'bogus'])->assertSessionHasErrors('playing_level');
        $this->assertSame('beginner', Recommendations::playingLevel($this->user));
        $this->post($url, ['playing_level' => ''])->assertRedirect();
        $this->assertSame($ids, $this->ids());
    }

    public function test_equal_matches_vary_composer_and_keep_only_six_with_stable_order()
    {
        $seed = $this->piece(['calm']);
        $this->user->favorites()->attach($seed);
        foreach (range(1, 8) as $i) $this->piece(['calm']);
        $other = Model::withoutEvents(fn () => create(Composer::class));
        $different = $this->piece(['calm'], ['composer_id' => $other->id]);
        $ids = $this->ids();
        $this->assertCount(6, $ids);
        $this->assertContains($different->id, array_slice($ids, 0, 2));
        $this->assertSame($ids, $this->ids());
    }

    public function test_removing_the_last_resource_refills_the_cached_row_with_an_eligible_piece()
    {
        $pieces = collect();
        foreach (range(1, 7) as $i) $pieces->push($this->piece([]));
        $original = $this->ids();
        $pieces->firstWhere('id', $original[0])->updateQuietly(['audio_path' => null]);
        $after = $this->ids();
        $this->assertCount(6, $after);
        $this->assertNotContains($original[0], $after);
        $this->assertSame($after, $this->ids());
    }

    public function test_excluded_accounts_do_not_use_marks_media_or_record_browsing()
    {
        $this->user->updateQuietly(['email' => \App\Activity\ExcludedAccounts::REQUIRED_EMAILS[0]]);
        $seed = $this->piece(['waltz']);
        $match = $this->piece(['waltz']);
        $editorial = $this->piece(['lively'], ['highlighted_at' => now()]);
        $this->mark($seed);
        $this->event($seed, 'media_pause', ['media_kind' => 'video', 'played_seconds' => 60, 'unique_watched_seconds' => 40]);
        app(RecentlyViewedPieces::class)->record($this->user, $seed);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->assertSame($editorial->id, $this->ids()[0]);
        $this->user->favorites()->attach($seed);
        $this->assertSame($match->id, $this->ids()[0]);
    }

    public function test_report_counts_only_matching_web_saves_after_a_visible_clicked_card_within_seven_days()
    {
        $piece = $this->piece();
        $seen = now('UTC')->subDays(10);
        $page = (string) Str::uuid();
        $meta = ['ranking_version' => 'musical-v1', 'cohort' => 'recent_views', 'section' => 'for_you', 'position' => 1];
        $write = function ($actor, $type, $at, $pageId, $metadata, $source = 'browser') use ($piece) {
            $this->event($piece, $type, $metadata, ['user_id' => $actor, 'page_id' => $pageId, 'occurred_at_utc' => $at, 'occurred_at' => $at->copy()->timezone(config('app.timezone')), 'source' => $source]);
        };
        foreach (range(1, 4) as $actor) {
            $write($actor, 'recommendation_seen', $seen, $page, $meta);
            $write($actor, 'click', $seen->copy()->addSecond(), $actor === 2 ? (string) Str::uuid() : $page, $meta);
            $write($actor, 'favorite_changed', $seen->copy()->addDays($actor === 3 ? 8 : 1), null, ['result' => $actor !== 4], 'server');
        }
        $this->artisan('discover:recommendation-outcomes')->expectsTable(
            ['Ranking / account history', 'Accounts shown', 'Clicked', 'Saved within 7 days', 'Saved after click', 'Save rate'],
            [['musical-v1 / recent_views', 4, 3, 2, 1, '50.0%']]
        )->assertExitCode(0);
    }
}
