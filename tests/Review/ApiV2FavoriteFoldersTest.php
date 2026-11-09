<?php

namespace Tests\Review;

use App\{Favorite, FavoriteFolder, Piece, Tag, Tutorial, User};
use App\Services\Api\V2\Tokens;
use App\Services\WebApp\AccessPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, Redis};
use Illuminate\Support\Str;

class ApiV2FavoriteFoldersTest extends ReviewTestCase
{
    private $user, $piece, $token;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        Redis::shouldReceive('get')->andReturn(null);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['super_user' => false]);
            $this->piece = create(Piece::class, ['cover_path' => 'fixtures/piece.jpg']);
            foreach (['level' => 'beginner', 'period' => 'classical', 'length' => 'short'] as $type => $name) $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            create(Tutorial::class, ['piece_id' => $this->piece->id, 'category' => 'performance', 'type' => 'Performance']);
        });
        $this->token = app(Tokens::class)->issue($this->user)['access_token'];
    }

    private function folder($name = 'Practice', $user = null)
    {
        return FavoriteFolder::create(['user_id' => ($user ?? $this->user)->id, 'name' => $name]);
    }

    private function url($folder, $piece = null)
    {
        return '/api/v2/favorite-folders/'.$folder->id.'/pieces/'.($piece ?? $this->piece->id);
    }

    private function headers($key = null)
    {
        return ['Authorization' => 'Bearer '.$this->token] + ($key !== null ? ['Idempotency-Key' => $key] : []);
    }

    private function change($method, $folder, $piece = null)
    {
        return $this->raw($method, $this->url($folder, $piece), '{}');
    }

    private function raw($method, $url, $body)
    {
        return $this->call($method, $url, [], [], [], $this->transformHeadersToServerVars($this->headers() + ['Accept' => 'application/json', 'Content-Type' => 'application/json']), $body);
    }

    private function listing()
    {
        return $this->getJson('/api/v2/favorite-folders?piece_id='.$this->piece->id, $this->headers());
    }

    private function createFolder(array $input, $key = null)
    {
        return $this->postJson('/api/v2/favorite-folders', $input, $this->headers($key));
    }

    private function policy(array $changes)
    {
        $policy = app(AccessPolicy::class);
        $this->assertTrue($policy->save(array_replace_recursive($policy->snapshot()['rules'], ['account' => $changes]), $policy->snapshot()['revision'], 1));
    }

    public function test_every_operation_requires_bearer_even_with_a_browser_session_and_submitted_identity()
    {
        $folder = $this->folder();
        $this->actingAs($this->user, 'web');
        foreach (['GET' => '/api/v2/favorite-folders?piece_id='.$this->piece->id, 'PUT' => $this->url($folder),
            'DELETE' => $this->url($folder), 'POST' => '/api/v2/favorite-folders'] as $method => $url) {
            $this->json($method, $url, ['user_id' => $this->user->id, 'name' => 'Unauthorized'])->assertUnauthorized()
                ->assertJsonPath('error.code', 'unauthenticated')->assertHeaderMissing('Set-Cookie');
        }
        DB::table('api_v2_tokens')->update(['expires_at' => now()->subMinute()]);
        $this->listing()->assertUnauthorized();
        $this->assertDatabaseCount('favorites', 0);
        $this->assertDatabaseCount('favorite_folders', 1);
    }

    public function test_empty_accounts_and_empty_folders_are_listed_with_typed_authoritative_state()
    {
        $this->listing()->assertOk()->assertJsonPath('data.piece_id', (string) $this->piece->id)
            ->assertJsonPath('data.folders', [])->assertJsonPath('data.is_favorited', false)
            ->assertJsonPath('data.favorite_folder_ids', [])->assertJsonPath('data.can_create_folder', true);
        $folder = $this->folder();
        $response = $this->listing()->assertOk()->assertJsonPath('data.folders.0', ['id' => (string) $folder->id, 'name' => 'Practice', 'piece_count' => 0, 'has_piece' => false])
            ->assertHeader('Cache-Control', 'no-store, private')->assertHeaderMissing('Set-Cookie');
        $this->assertTrue(Str::isUuid($response->json('meta.request_id')));
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        Http::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_put_and_delete_are_idempotent_and_preserve_memberships_in_other_folders()
    {
        $first = $this->folder('First'); $second = $this->folder('Second');
        foreach (range(1, 3) as $retry) $this->change('PUT', $first)->assertOk()->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.folder.piece_count', 1)->assertJsonPath('data.folder.has_piece', true)
            ->assertJsonPath('data.favorite_folder_ids', [(string) $first->id]);
        $this->assertDatabaseCount('favorites', 1);
        $this->change('PUT', $second)->assertOk()->assertJsonPath('data.favorite_folder_ids', [(string) $first->id, (string) $second->id]);
        foreach (range(1, 2) as $retry) $this->change('DELETE', $first)->assertOk()->assertJsonPath('data.is_favorited', true)
            ->assertJsonPath('data.folder.piece_count', 0)->assertJsonPath('data.folder.has_piece', false)
            ->assertJsonPath('data.favorite_folder_ids', [(string) $second->id]);
        foreach (range(1, 2) as $retry) $this->change('DELETE', $second)->assertOk()->assertJsonPath('data.is_favorited', false)->assertJsonPath('data.favorite_folder_ids', []);
        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_new_memberships_keep_contiguous_folder_order_and_counts()
    {
        $folder = $this->folder();
        $other = Model::withoutEvents(function () { return create(Piece::class); });
        $this->change('PUT', $folder)->assertOk();
        $this->change('PUT', $folder, $other->id)->assertOk()->assertJsonPath('data.folder.piece_count', 2);
        $this->assertSame([0, 1], Favorite::where('favorite_folder_id', $folder->id)->orderBy('order')->pluck('order')->all());
        $this->change('DELETE', $folder)->assertOk()->assertJsonPath('data.folder.piece_count', 1);
        $this->assertSame([0], Favorite::where('favorite_folder_id', $folder->id)->pluck('order')->all());
    }

    public function test_missing_and_foreign_resources_are_404_without_any_changes()
    {
        $other = Model::withoutEvents(function () { return create(User::class); });
        $foreign = $this->folder('Private', $other); $own = $this->folder();
        foreach (['PUT', 'DELETE'] as $method) {
            $this->change($method, $foreign)->assertNotFound()->assertJsonPath('error.code', 'not_found');
            $this->change($method, $own, '99999999')->assertNotFound();
            $missing = new FavoriteFolder(['id' => 99999999]);
            $this->change($method, $missing)->assertNotFound();
        }
        $this->getJson('/api/v2/favorite-folders?piece_id=99999999', $this->headers())->assertNotFound();
        $this->createFolder(['name' => 'Missing piece', 'piece_id' => '99999999'], (string) Str::uuid())->assertNotFound();
        $this->listing()->assertOk()->assertJsonCount(1, 'data.folders');
        $this->assertDatabaseCount('favorite_folders', 2);
        $this->assertDatabaseCount('favorites', 0);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 0);
    }

    public function test_unfiled_or_foreign_folder_rows_do_not_fill_the_heart_or_change_legacy_records()
    {
        $other = Model::withoutEvents(function () { return create(User::class); });
        $foreign = $this->folder('Private', $other);
        $this->user->favorites()->attach($this->piece);
        $this->user->favorites()->attach($this->piece, ['favorite_folder_id' => $foreign->id]);
        $other->favorites()->attach($this->piece, ['favorite_folder_id' => $foreign->id]);
        $this->listing()->assertOk()->assertJsonPath('data.is_favorited', false)->assertJsonPath('data.folders', []);
        $this->getJson('/api/v2/pieces/'.$this->piece->id, $this->headers())->assertOk()
            ->assertJsonPath('data.account.is_favorited', false)->assertJsonPath('data.account.favorite_writes_supported', true);
        $this->assertDatabaseCount('favorites', 3);
    }

    public function test_folder_creation_with_piece_and_retry_uses_one_durable_account_scoped_receipt()
    {
        $key = (string) Str::uuid(); $input = ['name' => 'Pieces to learn', 'piece_id' => (string) $this->piece->id];
        $first = $this->createFolder($input, $key)->assertCreated()->assertJsonPath('data.folder.piece_count', 1)->assertJsonPath('data.is_favorited', true);
        $retry = $this->createFolder($input, strtoupper($key))->assertCreated();
        $this->assertSame($first->json('data'), $retry->json('data'));
        $this->assertNotSame($first->json('meta.request_id'), $retry->json('meta.request_id'));
        $this->assertDatabaseCount('favorite_folders', 1); $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 1);
        $this->createFolder(['name' => 'Different name', 'piece_id' => $input['piece_id']], $key)->assertStatus(409)->assertJsonPath('error.code', 'idempotency_conflict');
        $this->createFolder(['name' => $input['name']], $key)->assertStatus(409);
        $other = Model::withoutEvents(function () { return create(User::class); });
        $this->token = app(Tokens::class)->issue($other)['access_token'];
        $this->createFolder($input, $key)->assertCreated();
        $this->assertDatabaseCount('favorite_folders', 2);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 2);
    }

    public function test_replay_returns_current_state_without_restoring_a_later_removal_or_recreating_a_deleted_folder()
    {
        $key = (string) Str::uuid(); $input = ['name' => 'Practice', 'piece_id' => (string) $this->piece->id];
        $id = $this->createFolder($input, $key)->assertCreated()->json('data.folder.id');
        $folder = FavoriteFolder::findOrFail($id);
        $this->change('DELETE', $folder)->assertOk();
        $folder->update(['name' => 'Renamed on web']);
        $this->policy(['folders' => ['registered' => 'blocked']]);
        $this->createFolder($input, $key)->assertCreated()->assertJsonPath('data.is_favorited', false)
            ->assertJsonPath('data.folder.name', 'Renamed on web')->assertJsonPath('data.folder.piece_count', 0);
        $folder->delete();
        $this->createFolder($input, $key)->assertNotFound();
        $this->assertDatabaseCount('favorite_folders', 0);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 1);
    }

    public function test_receipt_expires_after_24_hours_and_failed_requests_do_not_consume_the_key()
    {
        $key = (string) Str::uuid();
        $this->createFolder(['name' => 'First folder'], $key)->assertCreated();
        $this->travel(24)->hours();
        $this->createFolder(['name' => 'Second folder'], $key)->assertCreated();
        $this->assertDatabaseCount('favorite_folders', 2);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 1);
        $this->createFolder(['name' => 'First folder'], (string) Str::uuid())->assertStatus(422)->assertJsonPath('error.code', 'invalid_request');
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 1);
    }

    public function test_folder_only_creation_has_explicit_null_piece_state_and_an_empty_folder()
    {
        $this->createFolder(['name' => '  Empty folder  '])->assertCreated()->assertJsonPath('data.piece_id', null)
            ->assertJsonPath('data.is_favorited', null)->assertJsonPath('data.favorite_folder_ids', [])
            ->assertJsonPath('data.folder.name', 'Empty folder')->assertJsonPath('data.folder.has_piece', null)->assertJsonPath('data.folder.piece_count', 0);
        $this->listing()->assertOk()->assertJsonPath('data.folders.0.has_piece', false);
    }

    public function test_receipt_rechecks_folder_ownership_and_cascades_on_account_deletion()
    {
        $key = (string) Str::uuid(); $input = ['name' => 'Owned creation', 'piece_id' => (string) $this->piece->id];
        $id = $this->createFolder($input, $key)->assertCreated()->json('data.folder.id');
        $other = Model::withoutEvents(function () { return create(User::class); });
        DB::table('favorite_folders')->where('id', $id)->update(['user_id' => $other->id]);
        $this->createFolder($input, $key)->assertNotFound();
        $this->assertDatabaseCount('favorite_folders', 1);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 1);
        DB::table('users')->where('id', $this->user->id)->delete();
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 0);
        $this->listing()->assertUnauthorized();
    }

    public function test_saved_website_folder_and_piece_caps_apply_to_v2_and_removal_still_works()
    {
        $this->policy(['folders' => ['registered' => 'limited'], 'folder_pieces' => ['registered' => 'limited']]);
        foreach (['First folder', 'Second folder'] as $name) $this->createFolder(compact('name'))->assertCreated();
        $this->listing()->assertOk()->assertJsonPath('data.can_create_folder', false);
        $this->createFolder(['name' => 'Over limit'])->assertForbidden()->assertJsonPath('error.code', 'favorite_folder_limit_reached');
        $folder = $this->user->favoriteFolders()->first();
        $this->change('PUT', $folder)->assertOk();
        foreach (range(1, 4) as $i) {
            $piece = Model::withoutEvents(function () { return create(Piece::class); });
            $this->change('PUT', $folder, $piece->id)->assertOk();
        }
        $extra = Model::withoutEvents(function () { return create(Piece::class); });
        $this->change('PUT', $folder, $extra->id)->assertForbidden()->assertJsonPath('error.code', 'favorite_piece_limit_reached');
        $this->change('PUT', $folder)->assertOk()->assertJsonPath('data.folder.piece_count', 5);
        $this->change('DELETE', $folder)->assertOk()->assertJsonPath('data.folder.piece_count', 4);
        $this->change('PUT', $folder, $extra->id)->assertOk()->assertJsonPath('data.folder.piece_count', 5);
    }

    public function test_blocked_permissions_preserve_existing_memberships_and_atomic_creation_rolls_back()
    {
        $folder = $this->folder();
        $this->change('PUT', $folder)->assertOk();
        $this->policy(['folder_pieces' => ['registered' => 'blocked']]);
        $key = (string) Str::uuid(); $input = ['name' => 'Atomic folder', 'piece_id' => (string) $this->piece->id];
        $this->createFolder($input, $key)->assertForbidden()->assertJsonPath('error.code', 'favorite_saves_blocked');
        $this->assertDatabaseCount('favorite_folders', 1); $this->assertDatabaseCount('favorites', 1);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 0);
        $this->assertNull($folder->fresh()->web_color, 'Failed creation must also roll back palette writes.');
        $this->policy(['folder_pieces' => ['registered' => 'available'], 'folders' => ['registered' => 'blocked']]);
        $this->change('PUT', $folder)->assertOk();
        $this->change('DELETE', $folder)->assertOk();
        $this->change('PUT', $folder)->assertForbidden()->assertJsonPath('error.code', 'favorite_folders_blocked');
        $this->createFolder(['name' => 'Blocked folder'])->assertForbidden()->assertJsonPath('error.code', 'favorite_folders_blocked');
        $this->policy(['folders' => ['registered' => 'available']]);
        $this->createFolder($input, $key)->assertCreated();
    }

    public function test_super_user_override_is_shared_with_web_limits()
    {
        $this->policy(['folders' => ['registered' => 'blocked'], 'folder_pieces' => ['registered' => 'blocked']]);
        $this->user->updateQuietly(['super_user' => true]);
        $this->createFolder(['name' => 'Override folder', 'piece_id' => (string) $this->piece->id])->assertCreated()->assertJsonPath('data.is_favorited', true);
    }

    public function test_saved_member_permissions_are_enforced_without_refreshing_billing()
    {
        Model::withoutEvents(function () {
            $source = create(\App\Billing\Sources\Stripe::class, ['status' => 'trialing', 'renews_at' => now()->addWeek()]);
            \App\Billing\Membership::create(['user_id' => $this->user->id, 'source_id' => $source->id, 'source_type' => get_class($source)]);
        });
        $this->policy(['folders' => ['registered' => 'blocked', 'member' => 'available']]);
        $id = $this->createFolder(['name' => 'Member folder', 'piece_id' => (string) $this->piece->id])->assertCreated()->json('data.folder.id');
        $this->policy(['folder_pieces' => ['member' => 'blocked']]);
        $this->createFolder(['name' => 'Member blocked save', 'piece_id' => (string) $this->piece->id])->assertForbidden()->assertJsonPath('error.code', 'favorite_saves_blocked');
        $this->change('DELETE', FavoriteFolder::findOrFail($id))->assertOk()->assertJsonPath('data.is_favorited', false);
        Http::assertNothingSent();
    }

    public function test_invalid_data_and_non_json_requests_never_write_partial_state()
    {
        foreach ([[], ['name' => 'ab'], ['name' => str_repeat('x', 37)], ['name' => '   '], ['name' => []],
            ['name' => 'Practice', 'piece_id' => null], ['name' => 'Practice', 'piece_id' => $this->piece->id],
            ['name' => 'Practice', 'piece_id' => '-1'], ['name' => 'Practice', 'user_id' => 9]] as $input) {
            $this->createFolder($input)->assertStatus(422)->assertJsonPath('error.code', 'invalid_request');
        }
        $this->createFolder(['name' => 'Practice'], 'not-a-uuid')->assertStatus(422);
        foreach (['[]', 'null', '{bad', '"Practice"'] as $body) $this->raw('POST', '/api/v2/favorite-folders', $body)->assertStatus(422);
        $this->post('/api/v2/favorite-folders', ['name' => 'Practice'], $this->headers())->assertStatus(422);
        foreach (['', '?piece_id[]=1', '?piece_id=0', '?piece_id=1.5'] as $query) $this->getJson('/api/v2/favorite-folders'.$query, $this->headers())->assertStatus(422);
        $folder = $this->folder();
        $this->raw('PUT', $this->url($folder), '{"user_id":9}')->assertStatus(422);
        $this->assertDatabaseCount('favorite_folders', 1); $this->assertDatabaseCount('favorites', 0);
        $this->assertDatabaseCount('api_v2_favorite_folder_receipts', 0);
    }

    public function test_web_and_native_read_and_write_the_same_records_and_piece_detail_state()
    {
        $folder = $this->folder();
        $this->change('PUT', $folder)->assertOk();
        $this->getJson('/api/v2/pieces/'.$this->piece->id, $this->headers())->assertOk()->assertJsonPath('data.account.is_favorited', true);
        $this->actingAs($this->user, 'web');
        $this->postJson(route('webapp.users.favorites.update', $this->piece), ['folder_id' => $folder->id])->assertOk()
            ->assertJsonPath('status', false)->assertJsonPath('favorite.is_favorited', false);
        $this->listing()->assertOk()->assertJsonPath('data.is_favorited', false)->assertJsonPath('data.folders.0.piece_count', 0);
        $this->postJson(route('webapp.users.favorites.update', $this->piece), ['folder_id' => $folder->id])->assertOk()->assertJsonPath('status', true);
        $this->listing()->assertOk()->assertJsonPath('data.is_favorited', true)->assertJsonPath('data.folders.0.has_piece', true);
        $this->change('DELETE', $folder)->assertOk();
        $this->getJson('/api/v2/pieces/'.$this->piece->id, $this->headers())->assertOk()->assertJsonPath('data.account.is_favorited', false);
        $this->assertSame(0, $folder->fresh()->favorites_count);
        $this->postJson(route('webapp.users.favorites.folders.store'), ['name' => 'Created on web', 'piece_id' => $this->piece->id])->assertOk();
        $this->listing()->assertOk()->assertJsonCount(2, 'data.folders')->assertJsonPath('data.is_favorited', true);
        $this->assertDatabaseCount('favorites', 1);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_write_rate_limit_has_retry_after_and_rejected_request_changes_nothing()
    {
        $folder = $this->folder();
        foreach (range(1, 30) as $i) $this->change('PUT', $folder)->assertOk();
        $this->change('DELETE', $folder)->assertStatus(429)->assertHeader('Retry-After')->assertJsonPath('error.code', 'rate_limited')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseCount('favorites', 1);
    }
}
