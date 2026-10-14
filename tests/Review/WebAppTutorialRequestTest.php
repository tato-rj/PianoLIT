<?php

namespace Tests\Review;

use App\{Piece, TutorialRequest, User};
use App\Activity\Journey;
use App\Events\Tutorials\NewRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Crypt, DB, Event, Route};
use Illuminate\Support\Str;

class WebAppTutorialRequestTest extends ReviewTestCase
{
    private $piece, $user;

    public function setUp(): void
    {
        parent::setUp();
        config(['activity.minimum_visit_seconds' => 0]);
        Event::fake([NewRequest::class]);
        [$this->piece, $this->user] = Model::withoutEvents(function () {
            return [create(Piece::class), create(User::class, ['super_user' => true])->setAppends(['full_name'])];
        });
        Route::middleware(['web', \App\Http\Middleware\Logs\RecordWebAppLog::class])->get('/review/request-piece/{piece}', function () {
            return response('<html>Request page</html>');
        })->name('review.request-piece');
    }

    private function open()
    {
        $this->actingAs($this->user, 'web')->get('http://my.'.config('app.short_url').'/review/request-piece/'.$this->piece->id)->assertOk();
        return DB::table('activity_pages')->orderByDesc('opened_at')->first();
    }

    private function submit($page, $attempt, array $extra = [])
    {
        return $this->from($page->url)->post(route('webapp.users.tutorial-requests.store', $this->piece), array_merge([
            'video_types' => ['Synthesia'], '_activity_page' => Crypt::encryptString($page->id), '_activity_attempt' => $attempt,
        ], $extra));
    }

    public function test_success_records_a_server_confirmed_request_owned_by_the_session_and_compacts_its_browser_attempt()
    {
        $page = $this->open(); $attempt = (string) Str::uuid();
        $this->submit($page, $attempt, ['user_id' => 999])->assertRedirect($page->url)->assertSessionHas('status');
        $tutorial = TutorialRequest::first();
        $this->assertSame($this->user->id, $tutorial->user_id);
        $event = DB::table('activity_events')->where('type', 'tutorial_request_created')->first();
        $this->assertSame('server', $event->source);
        $this->assertSame($page->id, $event->page_id);
        $this->assertSame($tutorial->id, json_decode($event->metadata, true)['request_id']);
        $this->assertSame($attempt, json_decode($event->metadata, true)['attempt_id']);
        $this->postJson('http://my.'.config('app.short_url').'/activity/events', [
            'page_token' => Crypt::encryptString($page->id), 'events' => [[
                'id' => (string) Str::uuid(), 'type' => 'form_submit', 'elapsed_ms' => 0, 'sequence' => 1,
                'metadata' => ['control' => 'synthesia-request-form', 'attempt_id' => $attempt, 'piece_id' => $this->piece->id],
            ]],
        ])->assertNoContent();
        $actions = (new Journey)->get($page->visit_id)['groups'][0]['actions'];
        $this->assertSame(['tutorial_request_created'], $actions->pluck('type')->all());
        $this->assertSame('Received Synthesia request', \App\Activity\Presentation::action($actions->first()));
        Event::assertDispatched(NewRequest::class);
        $this->assertDatabaseHas('activity_events', ['type' => 'form_submit']);
    }

    public function test_rejections_record_the_actual_reason_and_create_no_new_request()
    {
        $page = $this->open();
        $existing = Model::withoutEvents(function () { return create(TutorialRequest::class, ['user_id' => $this->user->id, 'piece_id' => $this->piece->id]); });
        $this->submit($page, (string) Str::uuid())->assertRedirect($page->url)->assertSessionHas('error');
        $event = DB::table('activity_events')->where('type', 'tutorial_request_rejected')->first();
        $this->assertSame('pending_request', json_decode($event->metadata, true)['reason']);
        $existing->update(['published_at' => now()]);
        $this->submit($page, (string) Str::uuid())->assertRedirect($page->url)->assertSessionHas('error');
        $event = DB::table('activity_events')->where('type', 'tutorial_request_rejected')->orderByDesc('id')->first();
        $this->assertSame('already_requested', json_decode($event->metadata, true)['reason']);
        $this->assertDatabaseCount('tutorial_requests', 1);
        $this->assertDatabaseMissing('activity_events', ['type' => 'tutorial_request_created']);
        Event::assertNotDispatched(NewRequest::class);
    }

    public function test_membership_rejection_and_malformed_analytics_correlation_do_not_change_account_rules()
    {
        $this->user->update(['super_user' => false]);
        $page = $this->open();
        $this->submit($page, ['forged'])->assertRedirect(route('webapp.membership.pricing'));
        $event = DB::table('activity_events')->where('type', 'tutorial_request_rejected')->first();
        $this->assertSame('membership_required', json_decode($event->metadata, true)['reason']);
        $this->assertNull(json_decode($event->metadata, true)['attempt_id']);
        $this->assertDatabaseCount('tutorial_requests', 0);
    }

    public function test_cancel_buttons_are_not_submitters_in_either_rendered_form()
    {
        foreach (['synthesia-request', 'tutorial-request'] as $name) {
            $html = view('webapp.piece.'.$name, ['piece' => $this->piece])->render();
            $dom = new \DOMDocument; @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertSame(1, $xpath->query('//form//button[@data-bs-dismiss="modal" and @type="button"]')->length);
            $this->assertSame(1, $xpath->query('//form//button[@type="submit"]')->length);
        }
    }

    public function test_excluded_accounts_still_create_requests_without_activity()
    {
        $this->user->update(['email' => 'arthurvillar@gmail.com']);
        $page = (object) ['url' => 'http://my.'.config('app.short_url').'/pieces/'.$this->piece->id];
        $this->actingAs($this->user, 'web')->from($page->url)->post(route('webapp.users.tutorial-requests.store', $this->piece), ['video_types' => ['Synthesia']])->assertSessionHas('status');
        $this->assertDatabaseCount('tutorial_requests', 1);
        $this->assertDatabaseCount('activity_events', 0);
    }
}
