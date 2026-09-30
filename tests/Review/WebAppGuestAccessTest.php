<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Performance, Piece, Playlist, Tag, Tutorial, User};
use App\Api\Search;
use App\Blog\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Redis, Storage};
use Stevebauman\Location\Facades\Location;

class WebAppGuestAccessTest extends ReviewTestCase
{
    protected $piece, $freePiece, $playlist, $post, $tutorial;

    public function setUp(): void
    {
        parent::setUp();
        Location::shouldReceive('get')->never();
        Redis::shouldReceive('get')->andReturn(null);

        Model::withoutEvents(function () {
            $this->piece = create(Piece::class, ['name' => 'Guest repertoire test', 'is_free' => false, 'highlighted_at' => now(), 'score_path' => 'test-score.pdf']);
            $this->freePiece = create(Piece::class, ['composer_id' => $this->piece->composer_id, 'is_free' => true, 'highlighted_at' => now()]);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'] as $type => $name) {
                $tag = create(Tag::class, compact('type', 'name'));
                $this->piece->tags()->attach($tag);
                $this->freePiece->tags()->attach($tag);
            }
            $this->tutorial = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'synthesia', 'category' => 'lesson']);
            $this->playlist = create(Playlist::class, ['order' => 1, 'published_at' => now()]);
            $this->playlist->pieces()->attach([$this->piece->id, $this->freePiece->id]);
            $this->post = create(Post::class, ['published_at' => now()]);
        });
    }

    public function test_visitors_can_browse_all_main_pages_without_an_account()
    {
        foreach (['discover', 'welcome', 'explore', 'highlights', 'playlists', 'composers.index', 'tour', 'settings', 'my-pieces', 'users.profile', 'terms', 'privacy', 'membership.pricing', 'search.results'] as $name) {
            $this->get(route('webapp.'.$name))->assertOk();
            $this->assertGuest('web');
        }
        $this->assertSame(0, User::count());
        $this->assertSame(0, Favorite::count());
        $this->assertSame(0, FavoriteFolder::count());
        $this->assertDatabaseCount('locations', 0);
    }

    public function test_visitors_can_read_premium_pieces_and_nonfeatured_playlists()
    {
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('Guest repertoire test')
            ->assertDontSee('data-manage="save-to"', false)
            ->assertDontSee('function startRequest()', false)
            ->assertDontSee('id="synthesia-request-form"', false);
        $this->get(route('webapp.playlists.show', $this->playlist))->assertOk();
        $this->get(route('webapp.composers.show', $this->piece->composer))->assertOk();
        $this->get(route('webapp.blog.show', $this->post))->assertOk();

        foreach (['collection', 'composer', 'timeline', 'similar', 'audio'] as $name) {
            $this->get(route('webapp.pieces.'.$name, $this->piece))->assertOk();
        }
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->tutorial]))->assertOk();
        Storage::fake('public');
        Storage::disk('public')->put('test-score.pdf', 'test PDF fixture');
        $this->withExceptionHandling()->get(route('webapp.pieces.score', $this->piece))->assertForbidden();
    }

    public function test_piece_and_playlist_share_the_artwork_header_without_changing_playlist_content()
    {
        Model::withoutEvents(function () {
            $this->playlist->update([
                'name' => 'Lullabies',
                'description' => 'Quiet pieces for the evening.',
                'cover_path' => 'app/playlists/lullabies.jpg',
            ]);
        });

        $piece = $this->get(route('webapp.pieces.show', $this->piece))->assertOk();
        $playlist = $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertSee('Lullabies')->assertSee('Quiet pieces for the evening.')
            ->assertSee('Sort by')->assertSee('Filter by')
            ->assertSee($this->playlist->cover_image, false)
            ->assertDontSee('navbar-brand')->assertDontSee('width: 180px');

        foreach ([$piece, $playlist] as $response) {
            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, 'class="piece-background"'));
            $this->assertSame(1, substr_count($html, 'class="piece-background-sharp"'));
        }

        if ($directory = getenv('ARTWORK_HEADER_PREVIEW_DIR')) {
            foreach (['piece' => $piece, 'playlist' => $playlist] as $name => $response) {
                $html = str_replace('http://my.localhost', 'http://my.pianolit.test', $response->getContent());
                $html = str_replace('http://my.pianolit.test/storage/app/playlists/lullabies.jpg', 'http://my.pianolit.test/images/webapp/collections/night.webp', $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }

        Model::withoutEvents(function () { $this->playlist->update(['cover_path' => null]); });
        $this->get(route('webapp.playlists.show', $this->playlist))->assertOk()
            ->assertSee('/images/webapp/collections/featured.webp', false);
    }

    public function test_discover_omits_personal_suggestions_even_if_a_visitor_supplies_a_user_id()
    {
        Cache::put('app.discover', collect([['title' => 'Public', 'row' => 'gallery', 'type' => 'piece', 'content' => []]]), 60);
        $this->get(route('webapp.discover', ['user_id' => 123, 'id' => 123]))->assertOk()->assertDontSee('For you');
        $this->assertGuest('web');
        $this->assertDatabaseCount('locations', 0);
    }

    public function test_guest_search_ignores_caller_supplied_identity_and_hides_save_controls()
    {
        $request = Request::create(route('webapp.search.results'), 'GET', ['user_id' => 123]);
        $route = app('router')->getRoutes()->getByName('webapp.search.results');
        $route->bind($request);
        $request->setRouteResolver(function () use ($route) { return $route; });
        $this->assertNull((new Search($request))->getUserId());
        $this->getJson(route('webapp.search.results', ['search' => 'happy', 'model' => Tag::class, 'user_id' => 123]))
            ->assertOk()->assertSee('Guest repertoire test')->assertDontSee('data-manage="save-to"', false);
        $this->getJson(route('webapp.search.results', ['search' => '']))->assertOk();
        $this->getJson(route('webapp.search.count', ['search' => 'happy', 'model' => Tag::class, 'count' => 1]))->assertOk();
    }

    public function test_guest_saving_account_actions_and_legacy_api_impersonation_are_rejected()
    {
        $this->withExceptionHandling();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $routes = [
            ['POST', 'users.favorites.update', [$this->piece]],
            ['POST', 'users.favorites.folders.store', []],
            ['PATCH', 'users.favorites.folders.update', [1]],
            ['PATCH', 'users.favorites.folders.reorder', [1]],
            ['DELETE', 'users.favorites.folders.delete', [1]],
            ['GET', 'users.favorites.folders.show', [1]],
            ['GET', 'users.favorites.folders.pdf', [1]],
            ['GET', 'pieces.save-to', [$this->piece]],
            ['POST', 'pieces.share', [$this->piece]],
            ['GET', 'users.performances.upload-url', [$this->piece]],
            ['POST', 'users.performances.store', [$this->piece]],
            ['DELETE', 'users.performances.destroy', [1]],
            ['POST', 'users.performances.clap', [1]],
            ['POST', 'users.tutorial-requests.store', [$this->piece]],
            ['POST', 'membership.purchase', [1]],
            ['POST', 'membership.update.plan', []],
            ['GET', 'membership.edit', []],
        ];
        foreach ($routes as [$method, $name, $params]) {
            $this->json($method, route('webapp.'.$name, $params), ['user_id' => 123, 'name' => 'Guest save'])->assertUnauthorized();
        }
        $this->postJson('http://my.localhost/api/users/favorites/update', ['user_id' => $user->id, 'piece_id' => $this->piece->id])->assertUnauthorized();
        $this->assertGuest('web');
        $this->assertDatabaseCount('favorites', 0);
        $this->assertDatabaseCount('favorite_folders', 0);
        $this->assertDatabaseCount('tutorial_requests', 0);
        $this->assertDatabaseCount('performances', 0);
    }

    public function test_registered_nonpaying_users_still_have_save_controls_and_personal_suggestions()
    {
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $user = Model::withoutEvents(function () { return create(User::class); });
        $user->setAppends(['full_name']);
        $this->actingAs($user, 'web');
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()->assertSee('data-manage="save-to"', false);
        $this->get(route('webapp.my-pieces'))->assertOk()->assertSee('SUGGESTIONS');
        $this->postJson(route('webapp.users.favorites.update', $this->piece), ['user_id' => 999])->assertOk();
        $this->assertDatabaseHas('favorites', ['user_id' => 1, 'piece_id' => $this->piece->id]);
        $this->assertDatabaseMissing('favorites', ['user_id' => 999]);
        $this->get(route('webapp.discover', ['user_id' => 999]))->assertOk()->assertSee('For you');
    }

    public function test_my_pieces_shows_only_the_signed_in_users_folders_with_real_previews()
    {
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        [$user, $folder, $emptyFolder, $otherFolder] = Model::withoutEvents(function () {
            $user = create(User::class)->setAppends(['full_name']);
            $other = create(User::class);
            $folder = create(FavoriteFolder::class, ['user_id' => $user->id, 'name' => 'Practice favorites']);
            $emptyFolder = create(FavoriteFolder::class, ['user_id' => $user->id, 'name' => 'New folder']);
            $otherFolder = create(FavoriteFolder::class, ['user_id' => $other->id, 'name' => 'Private folder']);
            Favorite::create(['user_id' => $user->id, 'favorite_folder_id' => $folder->id, 'piece_id' => $this->piece->id, 'order' => 0]);
            Favorite::create(['user_id' => $user->id, 'favorite_folder_id' => $folder->id, 'piece_id' => $this->freePiece->id, 'order' => 1]);
            Favorite::create(['user_id' => $other->id, 'favorite_folder_id' => $otherFolder->id, 'piece_id' => $this->piece->id]);
            return [$user, $folder, $emptyFolder, $otherFolder];
        });

        $this->actingAs($user, 'web');
        $response = $this->get(route('webapp.my-pieces'))->assertOk()
            ->assertSee('Your favorites')->assertSee('2 folders · 2 pieces')
            ->assertSee('Practice favorites')->assertSee('New folder')
            ->assertSee('Guest repertoire test')->assertSee($this->piece->composer->short_name)
            ->assertSee('No pieces saved yet')->assertDontSee($otherFolder->name)
            ->assertSee('class="my-pieces-switch nav"', false)
            ->assertSee('id="favorites-tab"', false)->assertSee('id="suggestions-tab"', false)
            ->assertSee('data-target="#edit-folder-'.$folder->id.'"', false)
            ->assertSee('data-target="#delete-folder-'.$folder->id.'"', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//article[contains(@class, "my-pieces-folder")]')->length);
        $this->assertSame(2, $xpath->query('//article[contains(@class, "my-pieces-folder")][.//h3/a[text()="Practice favorites"]]//div[contains(@class, "my-pieces-folder__piece")]')->length);
        $this->assertSame(0, $xpath->query('//article[contains(@class, "my-pieces-folder")][.//h3/a[text()="New folder"]]//div[contains(@class, "my-pieces-folder__piece")]')->length);
    }

    public function test_folder_ordering_and_claps_use_the_signed_in_account()
    {
        $this->withExceptionHandling();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        [$user, $other, $folder, $foreignFolder, $first, $second, $foreignFavorite, $performance] = Model::withoutEvents(function () {
            $user = create(User::class)->setAppends(['full_name']);
            $other = create(User::class);
            $folder = create(FavoriteFolder::class, ['user_id' => $user->id]);
            $foreignFolder = create(FavoriteFolder::class, ['user_id' => $other->id]);
            $first = Favorite::create(['user_id' => $user->id, 'piece_id' => $this->piece->id, 'favorite_folder_id' => $folder->id, 'order' => 0]);
            $second = Favorite::create(['user_id' => $user->id, 'piece_id' => $this->freePiece->id, 'favorite_folder_id' => $folder->id, 'order' => 1]);
            $foreignFavorite = Favorite::create(['user_id' => $other->id, 'piece_id' => $this->piece->id, 'favorite_folder_id' => $foreignFolder->id]);
            $performance = Performance::create(['user_id' => $other->id, 'piece_id' => $this->piece->id, 'approved_at' => now()]);
            return [$user, $other, $folder, $foreignFolder, $first, $second, $foreignFavorite, $performance];
        });
        $this->actingAs($user, 'web');
        $this->get(route('webapp.users.favorites.folders.show', $folder))->assertOk();
        $this->getJson(route('webapp.users.favorites.folders.show', $foreignFolder))->assertForbidden();
        $this->patchJson(route('webapp.users.favorites.folders.reorder', $foreignFolder), ['ids' => [$foreignFavorite->id], 'user_id' => $other->id])->assertForbidden();
        $this->patchJson(route('webapp.users.favorites.folders.reorder', $folder), ['ids' => [$foreignFavorite->id]])->assertUnprocessable();
        $this->patchJson(route('webapp.users.favorites.folders.reorder', $folder), ['ids' => [$second->id, $first->id], 'user_id' => $other->id])->assertOk();
        $this->assertSame([$second->id, $first->id], $folder->favorites()->pluck('id')->all());
        $this->postJson(route('webapp.users.performances.clap', $performance), ['user_id' => $other->id])->assertOk()->assertJson(['claps_sum' => 1]);
        $this->assertDatabaseHas('claps', ['performance_id' => $performance->id, 'user_id' => $user->id, 'count' => 1]);
        $this->actingAs($other->setAppends(['full_name']), 'web');
        $this->postJson(route('webapp.users.performances.clap', $performance))->assertForbidden();
    }
}
