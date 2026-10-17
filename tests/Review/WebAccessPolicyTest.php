<?php

namespace Tests\Review;

use App\{Admin, Favorite, FavoriteFolder, Piece, Playlist, Tag, Tutorial, User};
use App\Services\WebApp\AccessPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};

class WebAccessPolicyTest extends ReviewTestCase
{
    private $user, $pieces, $playlist;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['super_user' => false]);
            $this->playlist = create(Playlist::class, ['published_at' => now(), 'cover_path' => null]);
            $this->pieces = collect();
            foreach (range(1, 6) as $index) {
                $piece = create(Piece::class, ['name' => 'Policy piece '.$index, 'is_free' => false, 'audio_path' => 'policy.wav', 'score_path' => 'policy.pdf', 'score_url' => null]);
                foreach (['level' => 'beginner', 'period' => 'classical', 'length' => 'short'] as $type => $name) $piece->tags()->attach(Tag::firstOrCreate(compact('type', 'name')));
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'performance']);
                create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Synthesia', 'category' => 'synthesia']);
                $this->playlist->pieces()->attach($piece->id);
                $this->pieces->push($piece);
            }
        });
    }

    private function save($changes)
    {
        $policy = app(AccessPolicy::class);
        $rules = array_replace_recursive($policy->snapshot()['rules'], $changes);
        $this->assertTrue($policy->save($rules, $policy->snapshot()['revision'], 1));
    }

    private function preview($name, $response)
    {
        if ($path = getenv('WEB_ACCESS_POLICY_PREVIEW_DIR')) file_put_contents($path.'/'.$name.'.html', $response->getContent());
    }

    public function test_saved_piece_rules_separate_video_synthesia_audio_score_exports_and_annotations()
    {
        $this->save(['regular' => [
            'performance' => ['guest' => 'available'], 'synthesia' => ['guest' => 'limited'],
            'audio' => ['guest' => 'blocked'], 'score' => ['guest' => 'available'], 'exports' => ['guest' => 'available'],
        ]]);
        $piece = $this->pieces->first();
        $response = $this->get(route('webapp.pieces.show', $piece))->assertOk();
        $this->preview('guest-custom-piece', $response);
        $doc = new \DOMDocument(); @$doc->loadHTML($response->getContent()); $xpath = new \DOMXPath($doc);
        $this->assertSame(0, $xpath->query('//video[@id="piece-performance"]/@data-media-preview')->length);
        $this->assertSame('10', $xpath->query('//video[@id="piece-synthesia"]/@data-media-preview')->item(0)->nodeValue);
        $this->assertSame(1, $xpath->query('//*[@data-audio-blocked="true"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="score-editor"]')->length);
        $response->assertSee('data-score-export-access="1"', false)->assertSee('data-annotations-url=""', false)->assertSee('#signup-annotations-modal');
        $this->get(route('webapp.pieces.audio', $piece))->assertOk()->assertSee('data-media-preview="0"', false);
        foreach (['Performance' => null, 'Synthesia' => '10'] as $type => $limit) {
            $tutorial = $piece->tutorials()->where('type', $type)->first();
            $fragment = $this->get(route('webapp.pieces.tutorial', [$piece, $tutorial]))->assertOk();
            if ($limit === null) $fragment->assertDontSee('data-media-preview', false);
            else $fragment->assertSee('data-media-preview="10"', false);
        }
    }

    public function test_annotation_permission_is_independent_but_requires_reading_and_preserves_saved_marks()
    {
        $this->actingAs($this->user, 'web');
        $piece = $this->pieces->first();
        $this->save(['regular' => ['score' => ['registered' => 'available'], 'annotations' => ['registered' => 'blocked']]]);
        $this->get(route('webapp.pieces.show', $piece))->assertOk()->assertSee('id="score-editor"', false)->assertSee('data-annotations-url=""', false);
        $params = ['score' => hash('sha256', $piece->score_path), 'fingerprint' => str_repeat('a', 32)];
        $this->getJson(route('webapp.pieces.score.annotations.show', $piece).'?'.http_build_query($params))->assertForbidden();
        $this->save(['regular' => ['annotations' => ['registered' => 'available']]]);
        $this->putJson(route('webapp.pieces.score.annotations.update', $piece), $params + ['revision' => 0, 'marks' => []])->assertOk();
        $this->save(['regular' => ['score' => ['registered' => 'blocked']]]);
        $this->getJson(route('webapp.pieces.score.annotations.show', $piece).'?'.http_build_query($params))->assertForbidden();
        $this->assertDatabaseCount('score_annotations', 1);
        $this->save(['regular' => ['score' => ['registered' => 'available']]]);
        $this->getJson(route('webapp.pieces.score.annotations.show', $piece).'?'.http_build_query($params))->assertOk()->assertJson(['revision' => 1]);
    }

    public function test_collection_defaults_and_saved_overrides_limit_the_actual_track_list()
    {
        $url = route('webapp.playlists.show', $this->playlist);
        $guest = $this->get($url)->assertOk()->assertDontSee('data-track data-id=', false)->assertSee('Sign up to explore collections.');
        $this->preview('guest-collection', $guest);
        $response = $this->actingAs($this->user, 'web')->get($url)->assertOk()->assertSee('first 3 pieces');
        $this->preview('registered-collection', $response);
        $this->assertSame(3, substr_count($response->getContent(), 'data-track data-id='));
        $this->save(['account' => ['collections' => ['registered' => 'available']]]);
        $response = $this->get($url)->assertOk()->assertDontSee('data-collection-limit', false);
        $this->preview('registered-open-collection', $response);
        $this->assertSame(6, substr_count($response->getContent(), 'data-track data-id='));
        $this->save(['account' => ['collections' => ['registered' => 'blocked']]]);
        $this->get($url)->assertOk()->assertDontSee('data-track data-id=', false);
    }

    public function test_folder_and_piece_caps_are_enforced_on_direct_writes_and_allow_removal()
    {
        $this->actingAs($this->user, 'web');
        $this->save(['account' => ['folders' => ['registered' => 'limited'], 'folder_pieces' => ['registered' => 'limited']]]);
        $url = route('webapp.users.favorites.folders.store');
        foreach (['Folder one', 'Folder two'] as $name) $this->postJson($url, compact('name'))->assertOk();
        $this->postJson($url, ['name' => 'Folder three'])->assertForbidden()->assertJson(['access_prompt' => '#piece-upgrade-modal']);
        $this->assertDatabaseCount('favorite_folders', 2);
        $this->preview('registered-folders-at-limit', $this->get(route('webapp.my-pieces'))->assertOk());
        $folder = $this->user->favoriteFolders()->first();
        foreach ($this->pieces->take(5) as $piece) $this->postJson(route('webapp.users.favorites.update', $piece), ['folder_id' => $folder->id])->assertOk()->assertJson(['status' => true]);
        $last = $this->pieces->last();
        $this->postJson(route('webapp.users.favorites.update', $last), ['folder_id' => $folder->id])->assertForbidden();
        $this->assertSame(5, Favorite::where('favorite_folder_id', $folder->id)->count());
        $this->postJson(route('webapp.users.favorites.update', $this->pieces->first()), ['folder_id' => $folder->id])->assertOk()->assertJson(['status' => false]);
        $this->postJson(route('webapp.users.favorites.update', $last), ['folder_id' => $folder->id])->assertOk()->assertJson(['status' => true]);
        $other = create(User::class);
        $foreign = FavoriteFolder::create(['user_id' => $other->id, 'name' => 'Foreign']);
        $this->postJson(route('webapp.users.favorites.update', $last), ['folder_id' => $foreign->id])->assertNotFound();
        $this->save(['account' => ['folder_pieces' => ['registered' => 'blocked']]]);
        $this->postJson(route('webapp.users.favorites.update', $this->pieces->first()))->assertForbidden();
        $this->assertSame(5, Favorite::where('favorite_folder_id', $folder->id)->count());
    }

    public function test_failed_folder_plus_piece_creation_rolls_back_the_folder()
    {
        $this->actingAs($this->user, 'web');
        $this->save(['account' => ['folder_pieces' => ['registered' => 'blocked']]]);
        $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Rollback folder', 'piece_id' => $this->pieces->first()->id])->assertForbidden();
        $this->assertDatabaseCount('favorite_folders', 0);
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_search_caps_apply_to_registered_and_guest_requests_and_cannot_be_paginated_around()
    {
        $url = route('webapp.search.results', ['catalogue' => 1, 'page' => 1]);
        $response = $this->getJson($url)->assertOk();
        $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
        $this->save(['account' => ['search' => ['guest' => 'available']]]);
        $response = $this->getJson($url)->assertOk()->assertDontSee('data-search-signup', false);
        $this->assertSame(6, substr_count($response->getContent(), 'data-sort-name='));
        $this->actingAs($this->user, 'web');
        $this->save(['account' => ['search' => ['registered' => 'limited']]]);
        $response = $this->getJson($url)->assertOk()->assertSee('first 3 search results');
        $this->assertSame(3, substr_count($response->getContent(), 'data-sort-name='));
        $this->assertSame('', $this->getJson(route('webapp.search.results', ['catalogue' => 1, 'page' => 2]))->assertOk()->getContent());
        $this->save(['account' => ['search' => ['registered' => 'blocked']]]);
        $this->getJson($url)->assertOk()->assertDontSee('data-sort-name=', false)->assertSee('currently locked');
    }

    public function test_paying_member_column_can_restrict_media_without_offering_another_membership()
    {
        Model::withoutEvents(function () {
            $source = create(\App\Billing\Sources\Stripe::class, ['created_at' => now()->subMonth(), 'renews_at' => now()->addMonth()]);
            \App\Billing\Membership::create(['user_id' => $this->user->id, 'source_type' => get_class($source), 'source_id' => $source->id]);
        });
        $this->save(['regular' => ['audio' => ['member' => 'blocked'], 'performance' => ['member' => 'limited']]]);
        $this->actingAs($this->user, 'web');
        $this->assertTrue($this->user->hasActiveSubscription());
        $response = $this->get(route('webapp.pieces.show', $this->pieces->first()))->assertOk();
        $this->preview('member-restricted-piece', $response);
        $response->assertSee('data-preview-modal="access-unavailable-modal"', false)->assertSee('data-audio-blocked="true"', false);
        $this->assertSame(10, app(AccessPolicy::class)->mediaLimit('performance', $this->user, $this->pieces->first()));
    }

    public function test_personal_history_stops_recording_when_blocked_without_erasing_previous_visits()
    {
        $this->actingAs($this->user, 'web');
        $this->get(route('webapp.pieces.show', $this->pieces->first()))->assertOk();
        $this->assertSame(1, DB::table('recently_viewed_pieces')->count());
        $this->save(['account' => ['personalization' => ['registered' => 'blocked']]]);
        $this->get(route('webapp.pieces.show', $this->pieces->last()))->assertOk();
        $this->assertSame(1, DB::table('recently_viewed_pieces')->count());
    }

    public function test_available_exports_work_independently_of_score_reading_on_the_web_route()
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put('policy.pdf', 'Original PDF fixture');
        $piece = $this->pieces->first();
        $url = route('webapp.pieces.score', $piece);
        $this->get($url)->assertForbidden();
        $this->save(['regular' => ['exports' => ['guest' => 'available']]]);
        $this->get($url)->assertOk();
        $response = $this->get(route('webapp.pieces.show', $piece))->assertOk()->assertSee('id="score-preview"', false)->assertSee('data-score-export-access="1"', false)->assertSee('ScoreEditor.printOriginal', false);
        $this->preview('guest-export-only-piece', $response);
        $this->save(['regular' => ['exports' => ['guest' => 'blocked']]]);
        $this->get($url)->assertForbidden();
    }

    public function test_super_user_override_and_mobile_media_attributes_remain_independent_of_policy()
    {
        $this->save(['regular' => ['audio' => ['member' => 'blocked'], 'score' => ['member' => 'blocked']]]);
        $this->user->update(['super_user' => true]);
        $piece = $this->pieces->first();
        $this->assertTrue($piece->hasWebMediaAccess($this->user, 'audio'));
        $this->assertTrue($piece->hasWebMediaAccess($this->user));
        $this->assertStringContainsString('policy.wav', $piece->audio);
        $this->assertSame(0, DB::table('recently_viewed_pieces')->count());
    }
}
