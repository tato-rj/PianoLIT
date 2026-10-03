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
                create(Tutorial::class, ['piece_id' => $piece->id]);
                $this->playlist->pieces()->attach($piece->id);
                Favorite::create(['user_id' => $this->user->id, 'piece_id' => $piece->id, 'favorite_folder_id' => $this->folder->id, 'order' => $index]);
                $this->pieces->push($piece);
            }
        });
    }

    public function test_guest_collection_has_player_previews_and_sign_in_favorites()
    {
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertSee('data-playlist-page', false)->assertSee('Create eScore')
            ->assertSee('data-preview="0"', false)->assertSee('data-preview="10"', false)
            ->assertSee('data-speed="0.75"', false)->assertSee('Audio unavailable')
            ->assertSee('Sign in to favorite')->assertDontSee('data-playlist-favorite', false);
        $this->assertSame(8, substr_count($response->getContent(), 'data-track data-id='));
        $this->assertSame(7, substr_count($response->getContent(), 'data-preview="10"'));
        $this->assertSame(0, substr_count($response->getContent(), 'piece-result'));
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) {
            file_put_contents($destination.'/collection.html', $this->previewHtml($response->getContent()));
        }
    }

    public function test_folder_player_preserves_account_order_and_super_user_access()
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('webapp.users.favorites.folders.show', $this->folder))->assertOk()
            ->assertSee('data-url-reorder=', false)->assertSee('Edit folder')->assertSee('Delete folder')
            ->assertSee('data-preview="10"', false);
        $response->assertSeeInOrder($this->pieces->map(function ($piece) { return 'data-piece-id="'.$piece->id.'"'; })->all(), false);
        $this->user->update(['super_user' => true]);
        $response = $this->get(route('webapp.users.favorites.folders.show', $this->folder))->assertOk()->assertDontSee('data-preview="10"', false);
        if ($destination = getenv('PLAYLIST_PREVIEW_DIR')) {
            file_put_contents($destination.'/folder.html', $this->previewHtml($response->getContent()));
        }
    }

    private function previewHtml($html)
    {
        $html = str_replace(['http://my.localhost', 'http://localhost'], 'http://127.0.0.1:8769', $html);
        return str_replace(['href="/css/', 'src="/js/'], ['href="http://127.0.0.1:8769/css/', 'src="http://127.0.0.1:8769/js/'], $html);
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

    public function test_collection_hearts_reflect_the_default_folder_only()
    {
        $this->actingAs($this->user, 'web');
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->assertSame(8, substr_count($response->getContent(), 'data-favorited="false"'));
        Favorite::create(['user_id' => $this->user->id, 'piece_id' => $this->pieces[0]->id]);
        $response = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->assertSame(1, substr_count($response->getContent(), 'data-favorited="true"'));
    }

    public function test_collection_escore_requires_a_session_and_filters_content_access()
    {
        $url = route('webapp.playlists.pdf', $this->playlist);
        $this->withExceptionHandling()->get($url)->assertRedirect(route('login'));
        $this->actingAs($this->user, 'web');
        $generator = \Mockery::mock(PDFGenerator::class);
        $generator->shouldReceive('pieces')->once()->withArgs(function ($pieces) { return $pieces->pluck('id')->all() === [$this->pieces[0]->id]; })->andReturnSelf();
        $generator->shouldReceive('request')->once()->with(['title' => 'My book', 'subtitle' => 'Piano', 'comment' => 'Practice'])->andReturnSelf();
        $generator->shouldReceive('generate')->once()->andReturnSelf();
        $generator->shouldReceive('stream')->once()->andReturn(response('PDF fixture'));
        $this->app->instance(PDFGenerator::class, $generator);
        $this->get($url.'?'.http_build_query(['title' => 'My book', 'subtitle' => 'Piano', 'comment' => 'Practice', 'user_id' => 999]))->assertOk()->assertSee('PDF fixture');
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

    public function test_empty_collection_and_invalid_escore_input_are_safe()
    {
        $this->playlist->pieces()->detach();
        $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()->assertSee('No pieces here yet.');
        $this->actingAs($this->user, 'web');
        $this->withExceptionHandling()->getJson(route('webapp.playlists.pdf', $this->playlist), ['Accept' => 'application/json'])->assertStatus(422);
        $this->getJson(route('webapp.playlists.pdf', $this->playlist).'?'.http_build_query(['title' => 'Book', 'subtitle' => 'Piano', 'comment' => 'Practice']))->assertForbidden();
    }
}
