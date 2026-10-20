<?php

namespace Tests\Review;

use App\{Admin, Piece, Playlist, Tag, Tutorial, User};
use App\Services\WebApp\Collections;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, DB, Redis};

class WebAppCollectionsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordAppLog::class,
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    private function playlist($name, $count = 6, $withTutorials = 6, $group = null, $featured = null)
    {
        return Model::withoutEvents(function () use ($name, $count, $withTutorials, $group, $featured) {
            $playlist = create(Playlist::class, ['name' => $name, 'group' => $group, 'featured' => $featured, 'order' => 7, 'published_at' => now()]);
            for ($i = 0; $i < $count; $i++) {
                $piece = create(Piece::class);
                foreach (['level' => 'beginner', 'period' => 'romantic', 'length' => 'short'] as $type => $tagName) {
                    $piece->tags()->attach(Tag::firstOrCreate(['type' => $type, 'name' => $tagName]));
                }
                if ($i < $withTutorials) create(Tutorial::class, ['piece_id' => $piece->id]);
                $playlist->pieces()->attach($piece->id);
            }
            return $playlist;
        });
    }

    public function test_guest_page_has_real_counts_links_and_unavailable_books_without_personal_progress()
    {
        $featured = $this->playlist('Lullabies', 7, 6);
        $this->playlist('Great for Beginners');
        $this->playlist('Hidden gems');
        $this->playlist("Burgmüller's studies");
        $response = $this->get(route('webapp.playlists', ['user_id' => 123]));
        $response->assertViewHas('inspiration', function ($cards) { return $cards->count() === 3; });
        $response->assertOk()->assertSee('Collections')->assertSee('The PianoLit series')
            ->assertSee('What would you like to play next?')->assertSee('Coming soon')
            ->assertSee('6 pieces')->assertDontSee('7 pieces')->assertDontSee('YOUR PATH')
            ->assertDontSee('Continue')->assertDontSee('THIS WEEK')
            ->assertSee('data-bs-target="#signup-collections-modal"', false)
            ->assertDontSee('href="'.route('webapp.playlists.show', $featured).'"', false);
        $this->assertGuest('web');
        $this->assertSame(8, substr_count($response->getContent(), 'collections-coming-soon'));
        $response->assertSeeInOrder(array_map(function ($number) {
            return 'Piano Solos · Book '.$number;
        }, range(1, 8)))->assertSee('Intermediate to advanced')
            ->assertSee('Advanced')->assertSee('Previous books')->assertSee('Next books');
        $this->assertLessThan(strpos($response->getContent(), 'inspiration-heading'), strpos($response->getContent(), 'id="pianolit-path"'));

        // Optional browser fixture from the isolated SQLite suite, never the local catalog.
        if ($destination = getenv('COLLECTIONS_PREVIEW_PATH')) {
            Model::withoutEvents(function () {
                foreach ([
                    'Lullabies' => 'Gentle piano pieces for quiet moments.',
                    'Great for Beginners' => 'Beautiful pieces for your first steps at the piano.',
                    'Hidden gems' => 'Discover a new favorite beyond the familiar.',
                    "Burgmüller's studies" => 'Build your technique through expressive little studies.',
                ] as $name => $subtitle) {
                    Playlist::where('name', $name)->update(['subtitle' => $subtitle]);
                }
            });
            foreach (['Sunday morning', 'At night', 'Wild flowers', 'True romance'] as $name) $this->playlist($name);
            $html = $this->get(route('webapp.playlists'))->getContent();
            $html = str_replace('http://my.localhost', 'http://my.pianolit.test', $html);
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="http://my.pianolit.test/css/', 'src="http://my.pianolit.test/js/'], $html);
            file_put_contents($destination, $html);
        }
    }

    private function setCollectionAccess($role, $state)
    {
        $policy = app(\App\Services\WebApp\AccessPolicy::class);
        $rules = $policy->snapshot()['rules'];
        $rules['account']['collections'][$role] = $state;
        $this->assertTrue($policy->save($rules, $policy->snapshot()['revision'], 1));
    }

    private function exportCollectionPreview($name, $response)
    {
        if ($path = getenv('COLLECTION_ACCESS_PREVIEW_DIR')) file_put_contents($path.'/'.$name.'.html', $response->getContent());
    }

    public function test_blocked_cards_and_direct_urls_open_the_matching_access_modal()
    {
        $playlist = $this->playlist('A collection to explore');
        $url = route('webapp.playlists.show', $playlist);
        foreach (['guest' => '#signup-collections-modal', 'registered' => '#collection-upgrade-modal', 'member' => '#access-unavailable-modal'] as $role => $target) {
            if ($role === 'registered') {
                $user = Model::withoutEvents(function () { return create(User::class, ['super_user' => false]); });
                $this->actingAs($user, 'web');
                $this->setCollectionAccess($role, 'blocked');
            }
            if ($role === 'member') {
                Model::withoutEvents(function () use ($user) {
                    $source = create(\App\Billing\Sources\Stripe::class, ['created_at' => now()->subMonth(), 'renews_at' => now()->addMonth()]);
                    \App\Billing\Membership::create(['user_id' => $user->id, 'source_type' => get_class($source), 'source_id' => $source->id]);
                });
                $user->unsetRelation('membership');
                $this->assertTrue($user->hasActiveSubscription());
                $this->setCollectionAccess($role, 'blocked');
            }
            $response = $this->get(route('webapp.playlists'))->assertOk()
                ->assertDontSee('href="'.$url.'"', false)->assertSee('id="'.ltrim($target, '#').'"', false);
            $dom = new \DOMDocument(); @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
            $links = $xpath->query('//a[@data-collection-blocked]');
            $this->assertSame(3, $links->length, 'Featured, inspiration and browse links all use the same guard.');
            foreach ($links as $link) {
                $this->assertSame($target, $link->getAttribute('href'));
                $this->assertSame($target, $link->getAttribute('data-bs-target'));
                $this->assertSame('modal', $link->getAttribute('data-bs-toggle'));
            }
            $this->exportCollectionPreview($role.'-blocked-index', $response);
            $this->get($url)->assertRedirect(route('webapp.playlists'))->assertSessionHas('collection_access_prompt', $target);
            $redirected = $this->get(route('webapp.playlists'))->assertOk()->assertSee('data-access-prompt="'.$target.'"', false);
            $this->exportCollectionPreview($role.'-redirected-index', $redirected);
        }
    }

    /** @dataProvider collectionCounts */
    public function test_limited_and_full_collection_views_keep_the_real_total($count)
    {
        // An attached piece without a tutorial is excluded on both the directory
        // and detail page, preserving the existing playable-piece definition.
        $playlist = $this->playlist('A collection of '.$count.' pieces', $count + 1, $count);
        $user = Model::withoutEvents(function () { return create(User::class, ['super_user' => false]); });
        $this->actingAs($user, 'web');
        $url = route('webapp.playlists.show', $playlist);
        $directory = $this->get(route('webapp.playlists'))->assertOk()->assertSee('href="'.$url.'"', false)->assertDontSee('data-collection-blocked', false);
        $this->exportCollectionPreview('limited-index-'.$count, $directory);
        $response = $this->get($url)->assertOk()->assertViewHas('totalPieceCount', $count)->assertViewHas('pieces', function ($pieces) { return $pieces->count() === 3; });
        $dom = new \DOMDocument(); @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame($count.' pieces', trim($xpath->query('//*[@data-collection-count]')->item(0)->textContent));
        $response->assertSee('Showing 3 of '.$count.' pieces');
        $this->exportCollectionPreview('limited-detail-'.$count, $response);
        $user->update(['super_user' => true]);
        $full = $this->get($url)->assertOk()->assertViewHas('totalPieceCount', $count)->assertViewHas('pieces', function ($pieces) use ($count) { return $pieces->count() === $count; })
            ->assertDontSee('data-collection-preview-count', false)->assertDontSee('data-collection-limit', false);
        $this->exportCollectionPreview('full-detail-'.$count, $full);
    }

    public static function collectionCounts()
    {
        return [[10], [12]];
    }

    public function test_small_limited_collections_do_not_claim_hidden_pieces()
    {
        $playlist = $this->playlist('Two pieces', 2, 2);
        $this->actingAs(Model::withoutEvents(function () { return create(User::class, ['super_user' => false]); }), 'web');
        $this->get(route('webapp.playlists.show', $playlist))->assertOk()->assertViewHas('totalPieceCount', 2)
            ->assertViewHas('pieces', function ($pieces) { return $pieces->count() === 2; })
            ->assertDontSee('data-collection-preview-count', false)->assertDontSee('data-collection-limit', false);
    }

    public function test_web_read_does_not_write_order_or_mutate_mobile_payloads()
    {
        $playlist = $this->playlist('Lullabies', 6, 6, null, 'Theme');
        $journey = $this->playlist('Basic 1', 6, 6, 'journey');
        Cache::put('app.playlists.order', [$playlist->id], 60);
        $url = route('api.playlists.index');
        $before = $this->getJson($url)->assertOk()->json();
        $journeyBefore = $this->getJson(route('api.playlists.index', ['group' => 'journey']))->assertOk()->json();
        $piecesUrl = route('api.playlists.show', $playlist);
        $piecesBefore = $this->getJson($piecesUrl)->assertOk()->json();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.playlists.index'))->assertOk()->assertDontSee('Basic 1');
        $this->get(route('admin.playlists.order'))->assertOk()->assertDontSee('Basic 1');
        Cache::forget('app.playlists.order');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $data = app(Collections::class)->data();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertCount(1, $queries);
        $this->assertStringStartsWith('select', strtolower($queries[0]['query']));
        $this->assertSame(7, $playlist->fresh()->order);
        $this->assertSame(7, $journey->fresh()->order);
        $this->assertFalse($data['featured']['playlist']->relationLoaded('pieces'));
        Cache::put('app.playlists.order', [$playlist->id], 60);
        $this->assertSame($before, $this->getJson($url)->assertOk()->json());
        $this->assertSame($journeyBefore, $this->getJson(route('api.playlists.index', ['group' => 'journey']))->assertOk()->json());
        $this->assertSame($piecesBefore, $this->getJson($piecesUrl)->assertOk()->json());
        $this->assertArrayHasKey('cover_image', $before[0]);
        $this->assertArrayHasKey('pieces', $before[0]);
    }

    public function test_admin_cover_controls_featured_inspiration_and_browse_images()
    {
        $playlist = $this->playlist('Lullabies', 6, 6, null, 'Theme');
        Model::withoutEvents(function () use ($playlist) {
            $playlist->update(['cover_path' => 'app/playlists/admin-selected.jpg']);
        });
        $data = app(Collections::class)->data();
        $this->assertSame($playlist->cover_image, $data['featured']['image']);
        $this->assertSame($playlist->cover_image, $data['inspiration'][0]['image']);
        $this->assertFalse($data['featured']['illustrated']);
        $this->assertSame('Theme', $data['featured']['category_label']);
        $this->assertSame('featured-'.hash('sha256', 'Theme'), $data['featured']['category']);
        $response = $this->get(route('webapp.playlists'))->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'src="'.$playlist->cover_image.'"'));
        $response->assertDontSee('/collections/night.webp');

        // Replacing the admin cover takes effect on the next web request.
        Model::withoutEvents(function () use ($playlist) {
            $playlist->update(['cover_path' => 'app/playlists/replacement.jpg']);
        });
        $this->get(route('webapp.playlists'))->assertOk()
            ->assertSee($playlist->cover_image, false)->assertDontSee('admin-selected.jpg');
    }

    public function test_browse_filters_use_unique_featured_tags_from_eligible_playlists()
    {
        $theme = $this->playlist('Lullabies', 6, 6, null, ' Theme ');
        $otherTheme = $this->playlist('A newly named collection', 6, 6, null, 'Theme');
        $composer = $this->playlist('Bach, thread by thread', 6, 6, null, 'Composer');
        $this->playlist('First steps', 6, 6, null, 'Level');
        $untagged = $this->playlist("Burgmüller's studies");
        $blank = $this->playlist('No featured tag', 6, 6, null, '   ');
        $withdrawn = $this->playlist('Withdrawn collection', 6, 6, null, 'Draft only');
        Model::withoutEvents(function () use ($withdrawn) { $withdrawn->update(['published_at' => null]); });
        $this->playlist('Journey collection', 6, 6, 'journey', 'Journey only');
        $this->playlist('Too few pieces', 5, 5, null, 'Too short');
        $this->playlist('Too few tutorials', 6, 4, null, 'No tutorials');

        $response = $this->get(route('webapp.playlists'))->assertOk();
        $data = $response->original->getData();
        $this->assertSame(['Composer', 'Level', 'Theme'], $data['categories']->values()->all());
        $cards = $data['playlists']->keyBy('playlist.id');
        $this->assertSame($cards[$theme->id]['category'], $cards[$otherTheme->id]['category']);
        $this->assertSame('Theme', $cards[$theme->id]['category_label']);
        $this->assertNotSame($cards[$theme->id]['category'], $cards[$composer->id]['category']);
        $this->assertNull($cards[$untagged->id]['category']);
        $this->assertNull($cards[$blank->id]['category']);

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $labels = [];
        foreach ($xpath->query('//*[@data-collection-filter]') as $button) {
            $labels[] = $button->textContent;
        }
        $this->assertSame(['All', 'Composer', 'Level', 'Theme'], $labels);
        $this->assertSame(2, $xpath->query('//*[@id="collections-grid"]/*[@data-collection-category="'.$cards[$theme->id]['category'].'"]')->length);

        if ($destination = getenv('COLLECTIONS_TAGS_PREVIEW_PATH')) {
            file_put_contents($destination, $response->getContent());
        }
    }

    public function test_featured_tag_edits_and_removal_take_effect_without_expiring_weekly_selection()
    {
        $playlist = $this->playlist('Lullabies', 6, 6, null, 'Mood');
        $original = app(Collections::class)->data();
        $selection = Cache::get('webapp.collections.weekly-selection');
        $oldKey = $original['playlists'][0]['category'];
        Model::withoutEvents(function () use ($playlist) {
            $playlist->update(['name' => 'A new name', 'featured' => 'Theme']);
        });

        $updated = app(Collections::class)->data();
        $this->assertSame(['Theme'], $updated['categories']->values()->all());
        $this->assertNotSame($oldKey, $updated['playlists'][0]['category']);
        $this->assertSame($selection, Cache::get('webapp.collections.weekly-selection'));
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('>Theme</button>', false)
            ->assertDontSee('>Mood</button>', false);

        Model::withoutEvents(function () use ($playlist) { $playlist->update(['featured' => null]); });
        $cleared = app(Collections::class)->data();
        $this->assertTrue($cleared['categories']->isEmpty());
        $this->assertNull($cleared['playlists'][0]['category']);
        $this->assertSame($selection, Cache::get('webapp.collections.weekly-selection'));
        $this->get(route('webapp.playlists'))->assertOk()->assertDontSee('data-collection-filter');
    }

    public function test_featured_tags_keep_distinct_labels_and_escape_html()
    {
        $labels = ['Etude', 'Étude', 'all', 'A & B "live"', '<b>Theme</b>'];
        foreach ($labels as $index => $label) {
            $this->playlist('Collection '.$index, 6, 6, null, $label);
        }
        $response = $this->get(route('webapp.playlists'))->assertOk();
        $data = $response->original->getData();
        $this->assertCount(count($labels), $data['categories']);
        $this->assertCount(count($labels), $data['playlists']->pluck('category')->unique());
        $this->assertNotContains('all', $data['categories']->keys()->all());
        $response->assertSee('A &amp; B &quot;live&quot;', false)
            ->assertSee('&lt;b&gt;Theme&lt;/b&gt;', false)->assertDontSee('<b>Theme</b>', false);

        $dom = new \DOMDocument;
        @$dom->loadHTML('<?xml encoding="utf-8" ?>'.$response->getContent());
        $xpath = new \DOMXPath($dom);
        $rendered = [];
        foreach ($xpath->query('//*[@data-collection-filter and @data-collection-filter!="all"]') as $button) {
            $rendered[$button->getAttribute('data-collection-filter')] = $button->textContent;
        }
        $this->assertSame($data['categories']->all(), $rendered);
    }

    public function test_random_selections_are_shared_and_cached_for_seven_days()
    {
        $startedAt = now()->startOfSecond();
        $this->travelTo($startedAt);
        try {
            $ids = [];
            foreach (['Lullabies', 'Great for Beginners', 'Hidden gems', 'Sunday morning', 'At night'] as $name) {
                $ids[] = $this->playlist($name)->id;
            }

            $key = 'webapp.collections.weekly-selection';
            $data = app(Collections::class)->data();
            $selection = Cache::get($key);
            $this->assertContains($selection['featured'], $ids);
            $this->assertCount(3, array_unique($selection['inspiration']));
            $this->assertEmpty(array_diff($selection['inspiration'], $ids));
            $this->assertSame($selection['featured'], $data['featured']['playlist']->id);
            $this->assertSame($selection['inspiration'], $data['inspiration']->pluck('playlist.id')->all());

            // Adding more eligible collections must not reshuffle a full selection.
            $this->playlist('New collection');
            $this->travelTo($startedAt->copy()->addDays(7)->subSecond());
            $response = $this->get(route('webapp.playlists'))->assertOk();
            $response->assertViewHas('featured', function ($card) use ($selection) {
                return $card['playlist']->id === $selection['featured'];
            })->assertViewHas('inspiration', function ($cards) use ($selection) {
                return $cards->pluck('playlist.id')->all() === $selection['inspiration'];
            });
            $this->assertSame($selection, Cache::get($key));

            $this->travelTo($startedAt->copy()->addDays(7)->addSecond());
            $this->assertFalse(Cache::has($key));
            $renewed = app(Collections::class)->data();
            $this->assertTrue(Cache::has($key));
            $this->assertCount(3, $renewed['inspiration']);
            $this->assertSame(Cache::get($key)['featured'], $renewed['featured']['playlist']->id);
        } finally {
            $this->travelBack();
        }
    }

    public function test_withdrawn_cached_collections_are_replaced_immediately()
    {
        foreach (['One', 'Two', 'Three', 'Four', 'Five'] as $name) $this->playlist($name);
        $data = app(Collections::class)->data();
        $withdrawnIds = array_unique(array_merge(
            [$data['featured']['playlist']->id], $data['inspiration']->pluck('playlist.id')->all()
        ));
        Model::withoutEvents(function () use ($withdrawnIds) {
            Playlist::whereIn('id', $withdrawnIds)->update(['published_at' => null]);
        });

        $data = app(Collections::class)->data();
        $this->assertNotContains($data['featured']['playlist']->id, $withdrawnIds);
        $this->assertEmpty(array_intersect($data['inspiration']->pluck('playlist.id')->all(), $withdrawnIds));
        $this->assertCount(min(3, 5 - count($withdrawnIds)), $data['inspiration']);
    }

    public function test_small_catalog_fills_available_slots_and_grows_without_waiting_a_week()
    {
        $empty = app(Collections::class)->data();
        $this->assertNull($empty['featured']);
        $this->assertTrue($empty['inspiration']->isEmpty());
        $this->assertFalse(Cache::has('webapp.collections.weekly-selection'));

        foreach (['One', 'Two', 'Three'] as $index => $name) {
            $this->playlist($name);
            $data = app(Collections::class)->data();
            $this->assertCount($index + 1, $data['inspiration']);
            $this->assertCount($index + 1, $data['inspiration']->pluck('playlist.id')->unique());
        }

        Model::withoutEvents(function () { Playlist::query()->update(['published_at' => null]); });
        $empty = app(Collections::class)->data();
        $this->assertNull($empty['featured']);
        $this->assertTrue($empty['inspiration']->isEmpty());
    }

    public function test_eligibility_fallback_and_empty_state()
    {
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('More music is on its way');
        $this->playlist('Too few pieces', 5, 5);
        $this->playlist('Too few tutorials', 6, 4);
        $this->playlist('Old journey', 6, 6, 'journey');
        $eligible = $this->playlist('Unmapped collection', 6, 5);
        $data = app(Collections::class)->data();
        $this->assertCount(1, $data['playlists']);
        $this->assertSame($eligible->id, $data['featured']['playlist']->id);
        $this->assertSame(5, $data['featured']['playlist']->pieces_count);
        $this->assertSame(asset('images/webapp/collections/featured.webp'), $data['featured']['image']);
        $this->assertTrue($data['categories']->isEmpty());
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('Unmapped collection')
            ->assertDontSee('Too few pieces')->assertDontSee('Old journey');
    }

    public function test_admin_creates_unpublished_playlists_and_requires_admin_session_to_publish()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->post(route('admin.playlists.store'), [
            'name' => 'Editorial draft',
            'subtitle' => 'Waiting for release',
            'description' => 'A collection in progress.',
        ])->assertRedirect();

        $playlist = Playlist::where('name', 'Editorial draft')->firstOrFail();
        $this->assertNull($playlist->published_at);
        $this->get(route('admin.playlists.index'))->assertOk()
            ->assertSee(route('admin.playlists.publication', $playlist), false)
            ->assertSee('Publish</button>', false);

        auth('admin')->logout();
        $this->withExceptionHandling();
        $this->patch(route('admin.playlists.publication', $playlist))
            ->assertRedirect(route('admin.login.show'));
        $this->assertNull($playlist->fresh()->published_at);
    }

    public function test_publication_controls_web_visibility_without_changing_mobile_playlists()
    {
        // Keep updated_at stable while comparing mobile payloads across admin edits.
        $this->travelTo(now()->startOfSecond());
        $playlist = $this->playlist('Editorial release');
        $playlist->update(['published_at' => null]);
        Cache::put('app.playlists.order', [$playlist->id], 60);

        $mobileListUrl = route('api.playlists.index');
        $mobilePiecesUrl = route('api.playlists.show', $playlist);
        $mobileList = $this->getJson($mobileListUrl)->assertOk()->json();
        $mobilePieces = $this->getJson($mobilePiecesUrl)->assertOk()->json();
        $this->assertArrayNotHasKey('published_at', $mobileList[0]);
        $this->get(route('webapp.playlists'))->assertOk()->assertDontSee('Editorial release');
        $this->withExceptionHandling();
        $this->get(route('webapp.playlists.show', $playlist))->assertRedirect(route('webapp.discover'));

        $this->actingAs(create(Admin::class), 'admin');
        $this->patch(route('admin.playlists.publication', $playlist))
            ->assertRedirect()->assertSessionHas('status', 'The playlist has been published.');
        $this->assertNotNull($playlist->fresh()->published_at);
        $this->get(route('admin.playlists.index'))->assertOk()->assertSee('Unpublish</button>', false);
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('Editorial release');
        $this->get(route('webapp.playlists.show', $playlist))->assertRedirect(route('webapp.playlists'));

        $this->patch(route('admin.playlists.publication', $playlist))
            ->assertRedirect()->assertSessionHas('status', 'The playlist has been unpublished.');
        $this->assertNull($playlist->fresh()->published_at);
        $this->get(route('webapp.playlists'))->assertOk()->assertDontSee('Editorial release');
        $this->get(route('webapp.playlists.show', $playlist))->assertRedirect(route('webapp.discover'));
        $this->assertSame($mobileList, $this->getJson($mobileListUrl)->assertOk()->json());
        $this->assertSame($mobilePieces, $this->getJson($mobilePiecesUrl)->assertOk()->json());
    }

    public function test_future_publication_date_does_not_show_early_on_web()
    {
        $playlist = $this->playlist('Future collection');
        $playlist->update(['published_at' => now()->addDay()]);
        $this->get(route('webapp.playlists'))->assertOk()->assertDontSee('Future collection');
        $this->withExceptionHandling();
        $this->get(route('webapp.playlists.show', $playlist))->assertRedirect(route('webapp.discover'));
    }

    public function test_additive_migration_keeps_existing_collections_visible()
    {
        $legacy = $this->playlist('Existing collection');
        $legacy->update(['published_at' => null]);
        $alreadyPublished = $this->playlist('Already published');
        $originalDate = $alreadyPublished->published_at;

        (new \AddPublishedAtToPlaylistsTable)->up();

        $this->assertNotNull($legacy->fresh()->published_at);
        $this->assertTrue($alreadyPublished->fresh()->published_at->equalTo($originalDate));
        $this->get(route('webapp.playlists'))->assertOk()->assertSee('Existing collection');
    }
}
