<?php

namespace Tests\Review;

use App\{Composer, FavoriteFolder, Piece, Playlist, Tag, Tutorial, User};
use App\Blog\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Redis};

class WebAppQueryRegressionTest extends ReviewTestCase
{
    protected $pieces, $user, $playlist, $folder;

    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class);
            $this->user->setAppends(['full_name']);
            $composer = create(Composer::class, ['gender' => 'female', 'ethnicity' => 'black', 'is_pedagogical' => false]);
            $tags = collect(['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'])
                ->map(function ($name, $type) { return create(Tag::class, compact('name', 'type')); });
            $this->playlist = create(Playlist::class, ['order' => 1]);
            $this->pieces = collect();
            for ($i = 0; $i < 12; $i++) {
                $piece = create(Piece::class, ['composer_id' => $composer->id, 'name' => 'Query piece '.$i, 'is_free' => $i === 0, 'highlighted_at' => now(), 'videos' => serialize([])]);
                $piece->tags()->attach($tags->pluck('id'));
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'synthesia']);
                $this->playlist->pieces()->attach($piece);
                $this->pieces->push($piece);
            }
            for ($i = 0; $i < 6; $i++) {
                $this->folder = create(FavoriteFolder::class, ['user_id' => $this->user->id]);
                $this->user->favorites()->attach($this->pieces[$i], ['favorite_folder_id' => $this->folder->id]);
            }
            create(Post::class, ['published_at' => now()]);
        });
    }

    public function test_page_query_budgets()
    {
        $pages = [
            'discover' => [], 'explore' => [], 'highlights' => [], 'playlists' => [],
            'composers.index' => [], 'composers.show' => [$this->pieces[0]->composer_id],
            'pieces.show' => [$this->pieces[0]], 'pieces.similar' => [$this->pieces[0]],
            'pieces.collection' => [$this->pieces[0]], 'pieces.timeline' => [$this->pieces[0]],
            'playlists.show' => [$this->playlist], 'search.results' => ['search' => 'happy', 'model' => Tag::class, 'lazy-load' => '', 'page' => 1],
            'search.count' => ['search' => 'happy', 'model' => Tag::class, 'count' => 1],
            'my-pieces' => [], 'settings' => [], 'users.profile' => [], 'pieces.save-to' => [$this->pieces[0]],
            'users.favorites.folders.show' => [$this->folder],
        ];
        $counts = [];
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($this->user, 'web');
            foreach ($pages as $name => $parameters) {
                if (! $signedIn && in_array($name, ['pieces.save-to', 'users.favorites.folders.show'])) continue;
                Cache::flush();
                $this->user->unsetRelations();
                DB::enableQueryLog();
                DB::flushQueryLog();
                $url = route('webapp.'.$name, $parameters);
                $response = in_array($name, ['highlights', 'search.results', 'search.count']) ? $this->getJson($url) : $this->get($url);
                $response->assertOk();
                $queries = DB::getQueryLog();
                DB::disableQueryLog();
                $key = ($signedIn ? 'member ' : 'guest ').$name;
                $counts[$key] = count($queries);
                if (getenv('WEBAPP_QUERY_REPORT')) {
                    file_put_contents(getenv('WEBAPP_QUERY_REPORT').'.sql', $key."\n".json_encode($queries, JSON_PRETTY_PRINT)."\n", FILE_APPEND);
                }
            }
        }
        if (getenv('WEBAPP_QUERY_REPORT')) file_put_contents(getenv('WEBAPP_QUERY_REPORT'), json_encode($counts, JSON_PRETTY_PRINT));
        $budgets = [
            'discover' => 52, 'explore' => 11, 'highlights' => 4, 'playlists' => 5,
            'composers.index' => 2, 'composers.show' => 2, 'pieces.show' => 18,
            'pieces.similar' => 10, 'pieces.collection' => 6, 'pieces.timeline' => 8,
            'playlists.show' => 6, 'search.results' => 7, 'search.count' => 3,
            'my-pieces' => 11, 'settings' => 1, 'users.profile' => 2,
            'pieces.save-to' => 4, 'users.favorites.folders.show' => 6,
        ];
        foreach ($counts as $key => $count) {
            $name = explode(' ', $key, 2)[1];
            $this->assertLessThanOrEqual($budgets[$name], $count, $key);
        }
    }
    public function test_card_queries_do_not_grow_with_the_number_of_pieces()
    {
        $this->actingAs($this->user, 'web');
        $counts = [];
        foreach ([2, 12] as $size) {
            DB::enableQueryLog();
            DB::flushQueryLog();
            $pieces = \App\Services\WebApp\PieceCards::load(Piece::take($size)->get());
            $html = view('webapp.search.results', compact('pieces'))->render();
            $counts[] = count(DB::getQueryLog());
            DB::disableQueryLog();
            $this->assertSame($size, substr_count($html, 'data-sort-name='));
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(5, $counts[1]);
    }

    public function test_loaded_media_keeps_the_same_payload_without_repeating_queries()
    {
        $piece = $this->pieces[0]->fresh();
        Model::withoutEvents(function () use ($piece) {
            foreach (['Synthesia', 'Harmonic analysis', 'Tutorial', 'Slow performance'] as $type) {
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => $type]);
            }
        });
        $expected = json_encode($piece->media);
        $piece->load('tutorials');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->assertSame($expected, json_encode($piece->media));
        $this->assertSame($expected, json_encode($piece->media));
        $this->assertTrue($piece->hasTutorials(['synthesia']));
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_favorite_card_status_is_scoped_to_the_signed_in_user()
    {
        $this->actingAs($this->user, 'web');
        $cards = \App\Services\WebApp\PieceCards::load(Piece::whereKey($this->pieces->pluck('id'))->get());
        $this->assertTrue($cards->find($this->pieces[0]->id)->webapp_is_favorited);
        $this->assertFalse($cards->find($this->pieces[11]->id)->webapp_is_favorited);
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($other, 'web');
        $cards = \App\Services\WebApp\PieceCards::load(Piece::whereKey($this->pieces->pluck('id'))->get());
        $this->assertSame(0, $cards->sum('webapp_is_favorited'));
    }

    public function test_browser_user_payload_does_not_serialize_loaded_favorites()
    {
        $this->actingAs($this->user, 'web');
        $this->user->load('favorites.tags');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $html = view('webapp.layouts.js-app')->render();
        $this->assertStringNotContainsString('Query piece', $html);
        $this->assertStringNotContainsString('logs_count', $html);
        $this->assertStringContainsString('"id":'.$this->user->id, $html);
        $this->assertCount(0, DB::getQueryLog());
        DB::disableQueryLog();
    }

    public function test_synthesia_releases_batch_load_piece_tags()
    {
        Tutorial::query()->update(['type' => 'Synthesia']);
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('webapp.pieces.show', $this->pieces[0]))->assertOk();
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();
        $lazyTags = $queries->filter(function ($query) {
            return strpos($query['query'], '"piece_tag"."piece_id" = ?') !== false;
        });
        $this->assertCount(0, $lazyTags);
    }

    public function test_profile_reuses_membership_and_newsletter_lists()
    {
        Model::withoutEvents(function () {
            $subscription = create(\App\Subscription::class, ['email' => $this->user->email]);
            for ($i = 0; $i < 6; $i++) {
                $list = create(\App\EmailList::class);
                if ($i % 2 === 0) $subscription->lists()->attach($list);
            }
            $source = create(\App\Billing\Sources\Apple::class);
            \App\Billing\Membership::create(['user_id' => $this->user->id, 'source_id' => $source->id, 'source_type' => get_class($source)]);
        });
        $this->actingAs($this->user, 'web');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->get(route('webapp.users.profile'))->assertOk()->assertSee('Your Apple membership');
        $queries = collect(DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertCount(4, $queries);
        $this->assertCount(1, $queries->filter(function ($query) { return strpos($query['query'], 'from "memberships"') !== false; }));
    }

    public function test_discover_does_not_add_browser_attributes_to_shared_cached_models()
    {
        $this->actingAs($this->user, 'web');
        $this->get(route('webapp.discover'))->assertOk();
        $this->get(route('webapp.discover'))->assertOk();
        foreach (Cache::get('app.discover') as $row) {
            if ($row['type'] !== 'piece') continue;
            foreach ($row['content'] as $piece) {
                $this->assertArrayNotHasKey('webapp_is_favorited', $piece->getAttributes());
                $this->assertArrayNotHasKey('webapp_has_performances', $piece->getAttributes());
            }
        }
    }

    public function test_save_to_status_updates_without_per_folder_queries()
    {
        $this->actingAs($this->user, 'web');
        $piece = $this->pieces[11];
        $url = route('webapp.users.favorites.update', ['piece' => $piece, 'folder_id' => $this->folder->id]);
        $response = $this->postJson($url)->assertOk()->assertJsonPath('status', true);
        $visibleSaved = function ($html) {
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            return (new \DOMXPath($dom))->query('//i[@name="saved" and not(contains(@style, "display: none"))]')->length;
        };
        $this->assertSame(1, $visibleSaved($response->json('html.list')));
        $this->postJson($url)->assertOk()->assertJsonPath('status', false);
        $response = $this->get(route('webapp.pieces.save-to', $piece))->assertOk();
        $this->assertSame(0, $visibleSaved($response->getContent()));
    }

    public function test_playlist_index_counts_match_mobile_without_loading_piece_models()
    {
        Model::withoutEvents(function () {
            foreach ([4, 5, 6] as $size) {
                $playlist = create(Playlist::class, ['order' => $size]);
                $playlist->pieces()->attach($this->pieces->take($size)->pluck('id'));
            }
            Tutorial::where('piece_id', $this->pieces[0]->id)->delete();
        });
        $api = new \App\Api\Api;
        $mobile = $api->playlists();
        DB::enableQueryLog();
        DB::flushQueryLog();
        $browser = $api->for('webapp')->playlists();
        $this->assertCount(1, DB::getQueryLog());
        DB::disableQueryLog();
        $this->assertSame($mobile->pluck('pieces_count', 'id')->all(), $browser->pluck('pieces_count', 'id')->all());
        foreach ($browser as $playlist) $this->assertFalse($playlist->relationLoaded('pieces'));
    }

}
