<?php

namespace Tests\Review;

use App\{Admin, Piece, SynthesiaRequest, Tag, User};
use App\Events\SynthesiaRequests\{NewRequest, RequestPublished};
use App\Mail\AutomaticEmailSettings;
use App\Services\Admin\SynthesiaRequestsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Event, Mail, Notification, Route, Schema};

class SynthesiaRequestsTest extends ReviewTestCase
{
    private function fixtures($attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return [$this->piece(['name' => 'Prelude 100%_!']), create(User::class, $attributes)];
        });
    }

    private function piece($attributes = [])
    {
        $piece = create(Piece::class, $attributes);
        foreach (['level' => 'intermediate', 'sublevel' => 'early intermediate', 'period' => 'romantic', 'length' => 'short'] as $type => $name) {
            $piece->tags()->attach(create(Tag::class, compact('type', 'name')));
        }
        return $piece;
    }

    public function test_legacy_post_keeps_the_exact_input_response_and_rejection_contracts()
    {
        Event::fake([NewRequest::class]);
        [$piece, $user] = $this->fixtures();
        $url = route('api.users.tutorial-requests.store');
        $this->assertSame('/api/users/tutorial-requests', parse_url($url, PHP_URL_PATH));
        $types = ['Slow performance', 'Harmonic analysis', 'Practicing tips'];
        $response = $this->postJson($url, ['user_id' => $user->id, 'piece_id' => $piece->id, 'video_types' => $types])->assertOk();
        $this->assertSame(json_encode("Your request has been received, we'll start working on it right away and it will be published within a few days. You'll receive an email when it is ready."), $response->getContent());
        $saved = SynthesiaRequest::first();
        $this->assertSame(serialize($types), $saved->types);
        $this->assertSame($user->id, $saved->user_id);
        Event::assertDispatched(NewRequest::class);
        $response = $this->postJson($url, ['user_id' => $user->id, 'piece_id' => $piece->id])->assertForbidden();
        $this->assertSame(json_encode('You have a pending request, please wait until we publish it before making a new one!'), $response->getContent());
        $saved->update(['published_at' => now()]);
        $response = $this->postJson($url, ['user_id' => $user->id, 'piece_id' => $piece->id])->assertForbidden();
        $this->assertSame(json_encode('Looks like you have already made a request for this piece, please send us an email if you were looking for something else.'), $response->getContent());
        $this->withExceptionHandling()->postJson($url, ['user_id' => $user->id])->assertUnprocessable()->assertJsonValidationErrors('piece_id');
        $this->assertDatabaseCount('synthesia_requests', 1);
    }

    public function test_legacy_optional_types_and_membership_behavior_remain_unchanged()
    {
        Event::fake([NewRequest::class]);
        [$piece, $user] = $this->fixtures(['super_user' => false]);
        $this->postJson(route('api.users.tutorial-requests.store'), ['user_id' => $user->id, 'piece_id' => $piece->id])->assertOk();
        $this->assertDatabaseHas('synthesia_requests', ['user_id' => $user->id, 'piece_id' => $piece->id, 'types' => null]);
    }

    public function test_both_permanent_legacy_get_urls_keep_piece_json_dates_order_and_relationship_names()
    {
        [$piece, $user] = $this->fixtures();
        $older = SynthesiaRequest::create(['user_id' => $user->id, 'piece_id' => $piece->id, 'types' => null,
            'created_at' => '2026-10-01 10:00:00', 'published_at' => '2026-10-03 10:00:00']);
        $other = Model::withoutEvents(function () { return $this->piece(); });
        $newer = SynthesiaRequest::create(['user_id' => $user->id, 'piece_id' => $other->id, 'types' => serialize(['Synthesia']), 'created_at' => '2026-10-05 10:00:00']);
        $responses = [];
        foreach (['api.tutorial-requests', 'api.users.tutorial-requests.show'] as $name) {
            $response = $this->getJson(route($name, ['user_id' => $user->id]))->assertOk();
            $responses[] = $response->json();
            $this->assertSame([$other->id, $piece->id], array_column($response->json(), 'id'));
            $this->assertSame('Oct 5, 2026', $response->json('0.request_created_at'));
            $this->assertNull($response->json('0.request_published_at'));
            $this->assertSame('Oct 3, 2026', $response->json('1.request_published_at'));
            $expected = Piece::find($piece->id)->toArray() + ['request_published_at' => 'Oct 3, 2026', 'request_created_at' => 'Oct 1, 2026'];
            $this->assertEquals($expected, $response->json('1'));
            $this->assertArrayNotHasKey('synthesia_requests', $response->json('1'));
        }
        $this->assertSame($responses[0], $responses[1]);
        $user->load('tutorialRequests')->setAppends([]);
        $this->assertArrayHasKey('tutorial_requests', $user->toArray());
        $this->assertArrayNotHasKey('synthesia_requests', $user->toArray());
        $this->assertSame($older->id, $user->publishedTutorialRequests()->first()->id);
        $this->assertSame($newer->id, $user->pendingTutorialRequests()->first()->id);
        foreach (['api.tutorial-requests', 'api.users.tutorial-requests.show'] as $name) {
            $this->assertSame('', $this->getJson(route($name, ['user_id' => 4294967294]))->assertOk()->getContent());
        }
    }

    public function test_web_and_already_open_legacy_forms_only_create_synthesia_using_session_identity()
    {
        Event::fake([NewRequest::class]);
        foreach (['webapp.users.synthesia-requests.store', 'webapp.users.tutorial-requests.store'] as $name) {
            [$piece, $user] = $this->fixtures(['super_user' => true]);
            $this->actingAs($user, 'web')->from(route('webapp.pieces.show', $piece))->post(route($name, $piece), [
                'user_id' => 999999, 'video_types' => ['Slow performance', 'Practicing tips'],
            ])->assertRedirect()->assertSessionHas('status');
            $this->assertDatabaseHas('synthesia_requests', ['piece_id' => $piece->id, 'user_id' => $user->id, 'types' => serialize(['Synthesia'])]);
        }
        $html = view('webapp.piece.synthesia-request', ['piece' => $piece])->render();
        $this->assertStringNotContainsString('video_types', $html);
        $this->assertStringNotContainsString('Slow performance', $html);
        $this->assertStringContainsString(route('webapp.users.synthesia-requests.store', $piece), $html);
    }

    public function test_admin_directory_uses_shared_table_status_before_actions_and_native_pagination()
    {
        [$piece, $user] = $this->fixtures();
        for ($i = 0; $i < 13; $i++) SynthesiaRequest::create(['piece_id' => $piece->id, 'user_id' => $user->id, 'published_at' => $i % 2 ? '2026-10-29 10:00:00' : null]);
        $url = route('admin.synthesia-requests.index');
        $this->withExceptionHandling()->get($url)->assertRedirect();
        $this->actingAs($user, 'web')->get($url)->assertRedirect();
        auth('web')->logout();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get($url)->assertOk()->assertSee('Synthesia Requests')->assertSee('Published')->assertSee('Pending')->assertSee('Oct 29, 2026')
            ->assertDontSee('Date published')->assertDontSee('view-request-types')->assertDontSee('Details')->assertDontSee('datatables.net');
        $this->assertSame(12, $response->viewData('requests')->count());
        $this->assertSame(13, $response->viewData('requests')->total());
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame('Status↕, sort ascending', trim($xpath->query('//table[@id="synthesia-requests-table"]/thead/tr/th[last()-1]')->item(0)->textContent));
        $this->assertSame(12, $xpath->query('//table[@id="synthesia-requests-table"]//a[@aria-label="Piece" and @target="_blank" and @rel="noopener noreferrer"]')->length);
        $this->assertSame(1, $this->get($url.'?page=2')->assertOk()->viewData('requests')->count());
        $this->get($url.'?search=100%25_!&status=published')->assertOk();
        $this->assertSame(6, $this->get($url.'?status=published')->assertOk()->viewData('requests')->total());
        foreach (['sort' => 'u.password', 'per_page' => 100000, 'status' => ['published'], 'search' => ['bad']] as $key => $bad) {
            $this->getJson($url.'?'.http_build_query([$key => $bad]))->assertUnprocessable();
        }
    }

    public function test_directory_search_is_literal_and_filters_the_whole_collection_before_pagination()
    {
        [$piece, $user] = $this->fixtures(['first_name' => 'Jamie', 'last_name' => 'Parker']);
        $match = SynthesiaRequest::create(['piece_id' => $piece->id, 'user_id' => $user->id]);
        $other = Model::withoutEvents(function () { return create(Piece::class, ['name' => 'Prelude 100XX']); });
        SynthesiaRequest::create(['piece_id' => $other->id, 'user_id' => $user->id, 'published_at' => now()]);
        $table = new SynthesiaRequestsTable;
        $data = $table->get(Request::create('/', 'GET', ['search' => '100%_!', 'sort' => 'piece_asc']));
        $this->assertSame([$match->id], $data['requests']->pluck('id')->all());
        $this->assertSame(2, $table->get(Request::create('/', 'GET', ['search' => 'Jamie Parker']))['requests']->total());
    }

    public function test_admin_keeps_historical_orphaned_requests_visible_without_piece_or_account_actions()
    {
        SynthesiaRequest::create(['user_id' => 999999, 'piece_id' => 999999]);
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.synthesia-requests.index'))->assertOk()
            ->assertSee('Piece unavailable')->assertSee('Account unavailable')->assertSee('Pending');
    }

    public function test_publication_is_idempotent_and_delete_preserves_filters_and_clamps_the_last_page()
    {
        Event::fake([RequestPublished::class]);
        [$piece, $user] = $this->fixtures();
        $saved = SynthesiaRequest::create(['piece_id' => $piece->id, 'user_id' => $user->id]);
        $this->actingAs(create(Admin::class), 'admin');
        $parameters = ['synthesiaRequest' => $saved, 'status' => 'pending', 'page' => 2];
        $url = route('admin.synthesia-requests.publish', $parameters);
        $this->patch($url)->assertRedirect(route('admin.synthesia-requests.index', ['status' => 'pending']));
        $date = $saved->fresh()->published_at->toDateTimeString();
        $this->travel(1)->days();
        $this->patch($url)->assertRedirect();
        $this->assertSame($date, $saved->fresh()->published_at->toDateTimeString());
        Event::assertDispatchedTimes(RequestPublished::class, 1);
        $this->delete(route('admin.synthesia-requests.destroy', $parameters))->assertRedirect(route('admin.synthesia-requests.index', ['status' => 'pending']));
        $this->assertDatabaseMissing('synthesia_requests', ['id' => $saved->id]);
    }

    public function test_old_admin_links_preserve_raw_queries_and_old_write_routes_remain_guarded()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $query = 'search=A%20B&search=A+B&status=pending';
        $this->get(route('admin.tutorial-requests.index').'?'.$query)->assertStatus(301)
            ->assertRedirect(route('admin.synthesia-requests.index').'?'.$query);
        $this->call('HEAD', route('admin.tutorial-requests.index').'?'.$query)->assertStatus(301);
        $this->assertFalse(Route::has('admin.synthesia-requests.show'));
        auth('admin')->logout(); $this->withExceptionHandling();
        $this->patchJson(route('admin.tutorial-requests.publish', 1))->assertUnauthorized();
        $this->deleteJson(route('admin.tutorial-requests.destroy', 1))->assertUnauthorized();
    }

    public function test_rename_and_rollback_preserve_original_rows_and_disabled_email_switches()
    {
        require_once database_path('migrations/2026_10_29_160000_rename_tutorial_requests_to_synthesia_requests.php');
        $migration = new \RenameTutorialRequestsToSynthesiaRequests;
        $migration->down();
        $row = ['id' => 498, 'user_id' => 27, 'piece_id' => 51, 'types' => serialize(['Slow performance', 'Synthesia']),
            'published_at' => '2026-08-01 10:00:00', 'created_at' => '2026-07-01 10:00:00', 'updated_at' => '2026-08-01 10:00:00'];
        DB::table('tutorial_requests')->insert($row);
        DB::table('automatic_email_settings')->insert(['email_key' => 'tutorial-published', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $this->assertFalse(Schema::hasTable('tutorial_requests'));
        $this->assertEquals($row, (array) DB::table('synthesia_requests')->first());
        $this->assertFalse(app(AutomaticEmailSettings::class)->enabled('synthesia-published'));
        $migration->down();
        $this->assertEquals($row, (array) DB::table('tutorial_requests')->first());
        $this->assertDatabaseHas('automatic_email_settings', ['email_key' => 'tutorial-published', 'enabled' => false]);
        $migration->up();
    }

    public function test_queued_legacy_email_and_model_identifiers_restore_against_the_renamed_table_and_switch()
    {
        [$piece, $user] = $this->fixtures();
        $saved = SynthesiaRequest::create(['piece_id' => $piece->id, 'user_id' => $user->id]);
        foreach ([\App\Mail\Tutorials\NewRequestEmail::class => 'synthesia-request', \App\Mail\Tutorials\RequestPublishedEmail::class => 'synthesia-published'] as $class => $key) {
            $legacy = \App\TutorialRequest::findOrFail($saved->id);
            $mail = unserialize(serialize(new $class($legacy)));
            $this->assertSame($saved->id, $mail->request->id);
            $this->assertSame('synthesia_requests', $mail->request->getTable());
            $this->assertSame($key, app(AutomaticEmailSettings::class)->mailableKey($mail));
            $mail->build();
            $this->assertStringContainsString('Synthesia', $mail->subject);
            $this->assertStringStartsWith('emails.synthesia-requests.', $mail->markdown);
        }
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_migration_resumes_after_a_separately_committed_table_rename()
    {
        require_once database_path('migrations/2026_10_29_160000_rename_tutorial_requests_to_synthesia_requests.php');
        $migration = new \RenameTutorialRequestsToSynthesiaRequests;
        $migration->down();
        DB::table('tutorial_requests')->insert(['id' => 713, 'user_id' => 81, 'piece_id' => 92]);
        DB::table('automatic_email_settings')->insert(['email_key' => 'tutorial-request', 'enabled' => false]);
        Schema::rename('tutorial_requests', 'synthesia_requests');
        $migration->up();
        $this->assertDatabaseHas('synthesia_requests', ['id' => 713, 'user_id' => 81, 'piece_id' => 92]);
        $this->assertFalse(app(AutomaticEmailSettings::class)->enabled('synthesia-request'));
    }

    public function test_conflicting_switches_stop_migration_before_any_schema_change()
    {
        require_once database_path('migrations/2026_10_29_160000_rename_tutorial_requests_to_synthesia_requests.php');
        $migration = new \RenameTutorialRequestsToSynthesiaRequests;
        $migration->down();
        DB::table('automatic_email_settings')->insert([
            ['email_key' => 'tutorial-request', 'enabled' => false],
            ['email_key' => 'synthesia-request', 'enabled' => true],
        ]);
        try {
            $migration->up();
            $this->fail('Conflicting switches must not be overwritten.');
        } catch (\RuntimeException $error) {
            $this->assertStringContainsString('Conflicting request email switches', $error->getMessage());
            $this->assertTrue(Schema::hasTable('tutorial_requests'));
            $this->assertFalse(Schema::hasTable('synthesia_requests'));
            $this->assertDatabaseHas('automatic_email_settings', ['email_key' => 'tutorial-request', 'enabled' => false]);
        } finally {
            DB::table('automatic_email_settings')->where('email_key', 'synthesia-request')->delete();
            $migration->up();
        }
    }
}
