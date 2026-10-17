<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Piece, Playlist, Tag, Tutorial, User};
use App\PDF\PDFGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class PlaylistPlayerTest extends ReviewTestCase
{
    private $playlist, $folder, $user, $pieces;

    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['super_user' => false])->setAppends(['full_name']);
            $this->folder = FavoriteFolder::create(['user_id' => $this->user->id, 'name' => 'Book 3']);
            $this->playlist = create(Playlist::class, ['name' => 'Book 3', 'description' => 'A collection of beautiful piano pieces.', 'published_at' => now()]);
            $this->pieces = collect();
            foreach (['Allegro', 'Andante', 'Vivace', 'Minuet', 'Allegro', 'Wild rider', 'Little waltz', 'Écossaise'] as $index => $name) {
                $piece = create(Piece::class, [
                    'name' => $name, 'nickname' => null, 'key' => 'Modal',
                    'catalogue_name' => 'Op.', 'catalogue_number' => 36 + $index,
                    'collection_number' => 1, 'movement_number' => null,
                    'audio_path' => $index === 7 ? null : 'playlist-fixture.wav',
                    'score_path' => 'score-'.$index.'.pdf', 'score_url' => $index === 6 ? 'https://example.com/score' : null, 'is_free' => $index === 0,
                ]);
                foreach (['level' => 'beginner', 'period' => 'classical', 'length' => 'short'] as $type => $tagName) {
                    $piece->tags()->attach(Tag::firstOrCreate(['type' => $type, 'name' => $tagName]));
                }
                $piece->composer->update(['name' => ['Muzio Clementi', 'Muzio Clementi', 'Muzio Clementi', 'Christian Petzold', 'Friedrich Kuhlau', 'Robert Schumann', 'Cornelius Gurlitt', 'Ludwig van Beethoven'][$index]]);
                $video = create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'performance']);
                if ($index === 0 || $index === 2) {
                    foreach (['Theme A', 'Theme B', 'Theme C', 'Return to theme B', 'Return to theme A'] as $section => $title) {
                        $video->moments()->create(['start_time' => $section * 4, 'end_time' => $section === 4 ? null : ($section + 1) * 4, 'title' => $title, 'comment' => 'Listen to the melody and how it changes in this section.', 'sort_order' => $section]);
                    }
                }
                $this->playlist->pieces()->attach($piece->id);
                Favorite::create(['user_id' => $this->user->id, 'piece_id' => $piece->id, 'favorite_folder_id' => $this->folder->id, 'order' => $index]);
                $this->pieces->push($piece);
            }
        });
    }

    private function openCollectionPolicy()
    {
        $policy = app(\App\Services\WebApp\AccessPolicy::class);
        $rules = $policy->snapshot()['rules'];
        $rules['account']['collections']['guest'] = $rules['account']['collections']['registered'] = 'available';
        $policy->save($rules, $policy->snapshot()['revision'], 1);
    }

    public function test_guest_collection_has_player_previews_and_action_specific_signup_prompts()
    {
        $this->openCollectionPolicy();
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertSee('data-playlist-page', false)->assertSee('Create eScore')
            ->assertSee('data-preview="0"', false)->assertSee('data-audio-blocked="true"', false)->assertSee('data-preview="10"', false)
            ->assertSee('data-speed="0.75"', false)->assertSee('Audio unavailable')
            ->assertSee('Sign up to favorite')->assertDontSee('data-playlist-favorite', false)
            ->assertDontSee('data-track-handle', false)->assertDontSee('data-url-reorder', false);
        $response->assertSee('data-bs-target="#signup-escore-modal"', false)
            ->assertSee('data-bs-target="#signup-favorites-modal"', false)
            ->assertSee('data-preview-modal="signup-audio-modal"', false)
            ->assertSee('Sign up to explore recordings.')
            ->assertDontSee('id="piece-upgrade-modal"', false)->assertDontSee('GO PREMIUM')
            ->assertDontSee('data-escore-form', false);
        $this->assertSame(8, substr_count($response->getContent(), 'data-track data-id='));
        $this->assertSame(7, substr_count($response->getContent(), 'data-preview="0"'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-preview="10"'));
        $this->assertSame(7, substr_count($response->getContent(), 'data-audio-blocked="true"'));
        $this->assertSame(0, substr_count($response->getContent(), 'piece-result'));
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) {
            file_put_contents($destination.'/collection.html', $this->previewHtml($response->getContent()));
        }
        if ($destination = getenv('ESCORE_PREMIUM_PREVIEW_DIR')) file_put_contents($destination.'/guest-collection.html', $this->previewHtml($response->getContent()));
        $this->actingAs($this->user, 'web');
        $registered = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertDontSee('data-audio-blocked="true"', false);
        $this->assertSame(7, substr_count($registered->getContent(), 'data-preview="10"'));
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) file_put_contents($destination.'/registered-collection.html', $this->previewHtml($registered->getContent()));
        $this->user->update(['super_user' => true]);
        $member = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertDontSee('data-audio-blocked="true"', false)->assertDontSee('data-preview="10"', false);
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) file_put_contents($destination.'/member-collection.html', $this->previewHtml($member->getContent()));
    }

    public function test_folder_player_preserves_account_order_and_super_user_access()
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('webapp.users.favorites.folders.show', $this->folder))->assertOk()
            ->assertSee('data-url-reorder=', false)->assertSee('data-track-handle', false)->assertSee('Edit folder')->assertSee('Delete folder')
            ->assertSee('data-preview="10"', false)->assertDontSee('data-audio-blocked="true"', false);
        $response->assertSeeInOrder($this->pieces->map(function ($piece) { return 'data-piece-id="'.$piece->id.'"'; })->all(), false);
        $this->user->update(['super_user' => true]);
        $response = $this->get(route('webapp.users.favorites.folders.show', $this->folder))->assertOk()->assertDontSee('data-preview="10"', false);
        $response->assertSee('escore-page--pending" data-escore-main-page aria-busy="true"', false)
            ->assertSee('data-escore-loader role="status"', false);
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) {
            file_put_contents($destination.'/folder.html', $this->previewHtml($response->getContent()));
        }
    }

    private function previewHtml($html)
    {
        $html = str_replace(['http://my.localhost', 'http://localhost'], 'http://127.0.0.1:8769', $html);
        return str_replace(['href="/css/', 'src="/js/'], ['href="http://127.0.0.1:8769/css/', 'src="http://127.0.0.1:8769/js/'], $html);
    }

    public function test_audio_uses_only_normal_performance_moments_and_escapes_the_payload()
    {
        $this->openCollectionPolicy();
        $piece = $this->pieces[0];
        $video = $piece->tutorials()->where('type', 'Performance')->first();
        $unsafeTitle = '<img src=x onerror=alert(1)> "Theme"';
        $video->moments()->first()->update(['title' => $unsafeTitle]);
        Model::withoutEvents(function () use ($piece) {
            foreach (['Slow performance', 'Tutorial', 'Synthesia', 'Performance'] as $type) {
                $video = create(Tutorial::class, ['piece_id' => $piece->id, 'type' => $type]);
                $video->moments()->create(['start_time' => 0, 'title' => 'Wrong recording '.$type, 'comment' => '', 'sort_order' => 0]);
            }
        });
        foreach ([route('webapp.playlists.show', $this->playlist), route('webapp.users.favorites.folders.show', $this->folder)] as $url) {
            if (strpos($url, '/users/') !== false) $this->actingAs($this->user, 'web');
            $response = $this->get($url)->assertOk()->assertSee('playlist-moments.js')->assertDontSee('Wrong recording')->assertDontSee('data-section-about', false)->assertDontSee('data-section-commentary', false)->assertDontSee('Listen to the melody and how it changes in this section.');
            $html = $response->getContent();
            $this->assertStringNotContainsString($unsafeTitle, $html);
            preg_match_all('/data-audio-moments="([^"]*)"/', $html, $matches);
            $moments = array_map(function ($value) { return json_decode(html_entity_decode($value, ENT_QUOTES), true); }, $matches[1]);
            $this->assertCount(8, $moments);
            $this->assertSame($unsafeTitle, $moments[0][0]['title']);
            $this->assertCount(5, $moments[0]);
            $this->assertSame([], $moments[1]);
            $this->assertSame(['id', 'start_time', 'end_time', 'title'], array_keys($moments[0][0]));
        }
        $this->assertArrayNotHasKey('moments', $video->load('moments')->toArray(), 'Web guide data stays hidden from mobile serialization');
    }

    public function test_audio_guide_query_counts_do_not_grow_with_track_count()
    {
        $this->openCollectionPolicy();
        $counts = [];
        foreach ([2, 8] as $size) {
            $this->playlist->pieces()->sync($this->pieces->take($size)->pluck('id')->all());
            app()->forgetInstance(\App\Services\WebApp\AccessPolicy::class);
            \DB::enableQueryLog(); \DB::flushQueryLog();
            $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
            $queries = \DB::getQueryLog(); \DB::disableQueryLog();
            $counts[] = count($queries);
            $this->assertSame($size, substr_count($response->getContent(), 'data-audio-moments='));
            $this->assertCount(1, array_filter($queries, function ($query) { return strpos($query['query'], 'from "video_moments"') !== false; }));
        }
        $this->assertSame($counts[0], $counts[1]);
        $this->assertLessThanOrEqual(9, $counts[1]);
    }

    public function test_dragged_folder_order_is_saved_only_for_its_owner()
    {
        $this->actingAs($this->user, 'web');
        $ids = $this->folder->favorites()->pluck('id')->reverse()->values()->all();
        $url = route('webapp.users.favorites.folders.reorder', $this->folder);
        $this->patchJson($url, ['ids' => $ids])->assertOk();
        $this->assertSame($ids, $this->folder->fresh()->favorites->pluck('id')->all());

        $other = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($other, 'web');
        $this->withExceptionHandling()->patchJson($url, ['ids' => array_reverse($ids)])->assertForbidden();
        $this->assertSame($ids, $this->folder->fresh()->favorites->pluck('id')->all());
    }

    public function test_collection_hearts_include_all_owned_folders_and_exclude_other_accounts()
    {
        $this->openCollectionPolicy();
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()->assertDontSee('data-track-handle', false)->assertDontSee('data-url-reorder', false);
        $this->assertSame(8, substr_count($response->getContent(), 'data-favorited="true"'));
        Favorite::where('user_id', $this->user->id)->where('piece_id', $this->pieces[0]->id)->delete();
        Favorite::create(['user_id' => 999, 'piece_id' => $this->pieces[0]->id]);
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'data-favorited="false"'));
        Favorite::create(['user_id' => $this->user->id, 'piece_id' => $this->pieces[0]->id]);
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->assertSame(8, substr_count($response->getContent(), 'data-favorited="true"'));
    }

    public function test_favorite_responses_keep_the_heart_full_until_the_last_owned_save_is_removed()
    {
        $this->actingAs($this->user, 'web');
        $piece = $this->pieces[0];
        $url = route('webapp.users.favorites.update', $piece);
        $otherFolder = FavoriteFolder::create(['user_id' => $this->user->id, 'name' => 'Recital']);
        Favorite::create(['user_id' => 999, 'piece_id' => $piece->id]);
        $state = ['piece_id' => $piece->id, 'is_favorited' => true];
        $this->postJson($url, ['folder_id' => $otherFolder->id])->assertOk()->assertJson(['status' => true, 'favorite' => $state]);
        $this->postJson($url, ['folder_id' => $this->folder->id])->assertOk()->assertJson(['status' => false, 'favorite' => $state]);
        $this->postJson($url)->assertOk()->assertJson(['status' => true, 'favorite' => $state]);
        $this->postJson($url)->assertOk()->assertJson(['status' => false, 'favorite' => $state]);
        $this->postJson($url, ['folder_id' => $otherFolder->id])->assertOk()->assertJson([
            'status' => false, 'favorite' => ['piece_id' => $piece->id, 'is_favorited' => false],
        ]);
        $this->assertDatabaseHas('favorites', ['user_id' => 999, 'piece_id' => $piece->id]);
    }

    public function test_creating_a_folder_with_a_piece_returns_saved_state_and_fills_every_piece_heart()
    {
        $this->actingAs($this->user, 'web');
        $piece = $this->pieces[0];
        Favorite::where('user_id', $this->user->id)->where('piece_id', $piece->id)->delete();
        $before = $this->get(route('webapp.pieces.show', $piece))->assertOk();
        $this->assertPieceHearts($before->getContent(), $piece, false, 2);
        $panel = $this->get(route('webapp.pieces.save-to', $piece))->assertOk();
        $other = Model::withoutEvents(function () { return create(User::class); });
        $created = $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Recital', 'piece_id' => $piece->id, 'user_id' => $other->id])->assertOk()
            ->assertJsonPath('favorite.piece_id', $piece->id)->assertJsonPath('favorite.is_favorited', true);
        $this->assertDatabaseHas('favorites', ['user_id' => $this->user->id, 'piece_id' => $piece->id, 'favorite_folder_id' => FavoriteFolder::where('name', 'Recital')->firstOrFail()->id]);
        $after = $this->get(route('webapp.pieces.show', $piece))->assertOk();
        $this->assertPieceHearts($after->getContent(), $piece, true, 2);
        $collection = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->assertPieceHearts($collection->getContent(), $piece, true, 1);
        $browseUrl = route('webapp.search.results', ['catalogue' => 1]);
        $results = $this->getJson($browseUrl)->assertOk();
        $this->assertPieceHearts($results->getContent(), $piece, true, 1);
        $browse = $this->get($browseUrl)->assertOk();
        $this->assertSame(1, substr_count($browse->getContent(), 'id="save-to-offcanvas"'));
        if ($destination = getenv('FAVORITE_HEART_PREVIEW_DIR')) {
            foreach (['piece-before' => $before, 'piece-after' => $after, 'collection' => $collection, 'browse' => $browse] as $name => $response) {
                file_put_contents($destination.'/'.$name.'.html', $this->previewHtml($response->getContent()));
            }
            file_put_contents($destination.'/panel.html', $panel->getContent());
            file_put_contents($destination.'/create.json', $created->getContent());
            file_put_contents($destination.'/piece-id.txt', $piece->id);
            file_put_contents($destination.'/results.html', $results->getContent());
            $url = route('webapp.users.favorites.update', $piece);
            $folder = FavoriteFolder::where('name', 'Recital')->firstOrFail();
            file_put_contents($destination.'/folder.html', $this->previewHtml($this->get(route('webapp.users.favorites.folders.show', $folder))->assertOk()->getContent()));
            foreach (['add-second' => $this->folder->id, 'quick-add' => null, 'quick-remove' => null, 'remove-first' => $folder->id, 'remove-last' => $this->folder->id, 'save-loose' => null, 'remove-loose' => null] as $name => $folderId) {
                file_put_contents($destination.'/'.$name.'.json', $this->postJson($url, ['folder_id' => $folderId])->assertOk()->getContent());
            }
        }
    }

    private function assertPieceHearts($html, Piece $piece, $saved, $count)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $xpath = new \DOMXPath($dom);
        $buttons = $xpath->query('//button[@data-favorite-piece="'.$piece->id.'"]');
        $this->assertSame($count, $buttons->length);
        foreach ($buttons as $button) {
            $this->assertSame($saved ? 'true' : 'false', $button->getAttribute('aria-pressed'));
            $this->assertSame($saved ? 'true' : 'false', $button->getAttribute('data-favorited'));
            $this->assertSame($saved ? 1 : 0, $xpath->query('.//i[contains(@class, "icon-filled")]', $button)->length);
        }
    }

    public function test_collection_escore_requires_premium_and_filters_copyrighted_scores()
    {
        $url = route('webapp.playlists.pdf', $this->playlist);
        $this->withExceptionHandling()->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->user, 'web');
        $this->getJson($url.'?title=Book')->assertForbidden()->assertJsonPath('message', 'Go Premium to create eScores.');
        \Illuminate\Database\Eloquent\Model::withoutEvents(function () {
            $source = create(\App\Billing\Sources\Stripe::class, ['status' => 'active', 'renews_at' => now()->addMonth()]);
            \App\Billing\Membership::create(['user_id' => $this->user->id, 'source_type' => \App\Billing\Sources\Stripe::class, 'source_id' => $source->id]);
        });
        $this->user->unsetRelation('membership');
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) { return $pieces->count() === 7 && !$pieces->contains('id', $this->pieces[6]->id); })->andReturnSelf();
        $generator->shouldReceive('request')->once()->with(['title' => 'My book', 'subtitle' => 'Piano', 'comment' => 'Practice', 'creator' => $this->user->full_name], true, true)->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('stream')->once()->andReturn(response('PDF fixture'));
        $this->app->instance(PDFGenerator::class, $generator);
        $this->get($url.'?'.http_build_query(['title' => 'My book', 'subtitle' => 'Piano', 'comment' => 'Practice', 'user_id' => 999]))->assertOk()->assertSee('PDF fixture');
    }

    public function test_free_account_escore_buttons_open_the_existing_premium_prompt()
    {
        $this->actingAs($this->user, 'web');
        foreach (['free-collection' => route('webapp.playlists.show', $this->playlist), 'free-folder' => route('webapp.users.favorites.folders.show', $this->folder)] as $name => $url) {
            $response = $this->get($url)->assertOk()->assertSee('data-bs-target="#piece-upgrade-modal"', false)->assertDontSee('data-escore-form', false)->assertSee(route('webapp.membership.pricing'), false);
            $this->assertSame(1, substr_count($response->getContent(), 'id="piece-upgrade-modal"'));
            if ($destination = getenv('ESCORE_PREMIUM_PREVIEW_DIR')) file_put_contents($destination.'/'.$name.'.html', $this->previewHtml($response->getContent()));
        }
    }

    public function test_collection_escore_excludes_copyrighted_and_unpublished_content()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) { return $pieces->count() === 7 && !$pieces->contains('id', $this->pieces[6]->id); })->andReturnSelf();
        $generator->shouldReceive('request')->andReturnSelf();
        $generator->shouldReceive('generate')->andReturnSelf();
        $generator->shouldReceive('stream')->andReturn(response('PDF fixture'));
        $this->app->instance(PDFGenerator::class, $generator);
        $parameters = ['title' => 'Book', 'subtitle' => 'Piano', 'comment' => 'Practice'];
        $url = route('webapp.playlists.pdf', $this->playlist).'?'.http_build_query($parameters);
        $this->get($url)->assertOk();
        $this->playlist->update(['published_at' => null]);
        $this->withExceptionHandling()->get($url)->assertRedirect(route('webapp.discover'));
    }

    public function test_collection_image_cover_uses_saved_image_and_custom_top_color()
    {
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $this->playlist->update(['cover_path' => 'app/playlists/collection-cover.png']);
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->andReturnSelf();
        $generator->shouldReceive('request')->once()->withArgs(function ($options) {
            return $options['color'] === '#c4e8dc' && !isset($options['cover_image']) && !isset($options['cover_path']);
        })->andReturnSelf();
        $generator->shouldReceive('collectionCover')->once()->with('app/playlists/collection-cover.png')->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('download')->once()->andReturn(response('%PDF-fixture'));
        $this->app->instance(PDFGenerator::class, $generator);
        $this->postJson(route('webapp.playlists.pdf', $this->playlist), ['title' => 'Book', 'color' => '#c4e8dc', 'cover_image' => 'https://example.com/untrusted.jpg', 'cover_path' => '/etc/passwd'])->assertOk();
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()->assertSee('data-escore-image-cover="true"', false)->assertSee('Top background color')->assertSee($this->playlist->cover_image, false);
        $response->assertSee('name="color" value="#ebebeb"', false);
        if ($destination = getenv('ESCORE_IMAGE_COLLECTION_HTML')) file_put_contents($destination, $this->previewHtml($response->getContent()));
        $this->playlist->update(['cover_path' => null]);
        $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()->assertDontSee('data-escore-image-cover', false);
    }

    public function test_empty_collection_and_invalid_escore_input_are_safe()
    {
        $this->openCollectionPolicy();
        $this->playlist->pieces()->detach();
        $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()->assertSee('No pieces here yet.');
        $this->actingAs($this->user, 'web');
        $this->user->update(['super_user' => true]);
        $this->withExceptionHandling()->getJson(route('webapp.playlists.pdf', $this->playlist), ['Accept' => 'application/json'])->assertStatus(422);
        $this->getJson(route('webapp.playlists.pdf', $this->playlist).'?'.http_build_query(['title' => 'Book', 'subtitle' => 'Piano', 'comment' => 'Practice']))->assertForbidden();
    }
}
