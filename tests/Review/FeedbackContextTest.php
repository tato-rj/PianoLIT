<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, Piece, Tag, User};
use App\Api\Api;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Crypt, Redis};

class FeedbackContextTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        Cache::put('app.discover'.Api::WEB_DISCOVER_CACHE_SUFFIX, collect([]), 60);
    }

    private function contextToken($response)
    {
        preg_match('/name="page_context" value="([^"]+)"/', $response->getContent(), $match);
        $this->assertNotEmpty($match[1] ?? null);
        $this->withCookies([config('session.cookie') => session()->getId()])->withCredentials();
        return html_entity_decode($match[1], ENT_QUOTES);
    }

    public function test_standard_guest_pages_share_one_launcher_and_one_modal_and_settings_has_a_second_entry()
    {
        foreach (['discover', 'explore', 'search.results', 'playlists', 'my-pieces', 'composers.index', 'settings', 'terms', 'privacy'] as $name) {
            $response = $this->get(route('webapp.'.$name))->assertOk();
            $html = $response->getContent();
            $this->assertSame(1, substr_count($html, 'data-feedback-launcher'), $name);
            $this->assertSame(1, substr_count($html, 'id="feedback-modal"'), $name);
            $this->assertStringContainsString('btn btn-secondary btn-sm feedback-launcher', $html);
            $this->assertStringContainsString('icon-message-square', $html);
            $this->assertStringNotContainsString('GIVE YOUR FEEDBACK', $html);
            $this->contextToken($response);
        }
        $this->get(route('webapp.settings'))->assertSee('Send feedback')->assertSee('aria-controls="feedback-modal"', false);
        $this->getJson(route('webapp.search.results', ['catalogue' => 1]))->assertOk()
            ->assertDontSee('data-feedback-launcher', false)->assertDontSee('id="feedback-modal"', false);
        $this->assertGuest('web');
        $this->assertDatabaseCount('feedback_submissions', 0);
    }

    public function test_questionnaire_auth_and_checkout_screens_have_no_feedback_utility()
    {
        $this->get(route('webapp.tour'))->assertOk()->assertDontSee('data-feedback-launcher', false)->assertDontSee('id="feedback-modal"', false);
        foreach (['login', 'register'] as $route) $this->get(route($route))->assertOk()->assertDontSee('data-feedback-launcher', false);

        // Render the shared layout with the real checkout route, without billing calls.
        $request = \Illuminate\Http\Request::create(route('webapp.membership.checkout', 1));
        $route = app('router')->getRoutes()->match($request);
        $request->setRouteResolver(function () use ($route) { return $route; });
        $this->app->instance('request', $request);
        $html = view('webapp.layouts.app')->render();
        $this->assertStringNotContainsString('data-feedback-launcher', $html);
        $this->assertStringNotContainsString('id="feedback-modal"', $html);
    }

    public function test_page_context_is_saved_without_query_strings_and_client_identity_or_arbitrary_context_cannot_replace_it()
    {
        $user = Model::withoutEvents(function () { return create(User::class, ['email' => 'elena.dare@gmail.com']); });
        $this->actingAs($user, 'web');
        $page = $this->get(route('webapp.settings', ['email' => 'private@example.test', 'token' => 'secret', 'modal' => 'feedback-modal']))->assertOk();
        $token = $this->contextToken($page);
        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) Version/18.0 Mobile/15E148 Safari/604.1')
            ->postJson(route('webapp.feedback.store'), [
                'rating' => 'not_bad', 'message' => 'Settings feedback', 'page_context' => $token, 'device_type' => 'mobile',
                'user_id' => 999999, 'piece_id' => 999999, 'context' => ['url' => 'https://untrusted.example/secret'],
            ])->assertCreated()->assertExactJson(['saved' => true]);
        $submission = FeedbackSubmission::first();
        $this->assertSame($user->id, $submission->user_id);
        $this->assertSame(route('webapp.settings'), $submission->context['url']);
        $this->assertSame('webapp.settings', $submission->context['route']);
        $this->assertNull($submission->context['piece_id']);
        $this->assertSame('mobile', $submission->context['device_type']);
        $this->assertSame('Safari', $submission->context['browser']);
        $this->assertSame('iOS', $submission->context['os']);
        $this->assertStringNotContainsString('secret', json_encode($submission->context));
        $this->assertNotNull($submission->created_at);

        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.feedback.index'))->assertOk()->assertSee('Context')->assertSee('Safari')->assertSee('iOS')
            ->assertSee(route('webapp.settings'))->assertSee('webapp.settings');
    }

    public function test_piece_and_composer_context_come_from_the_route_bound_models_for_guests()
    {
        $piece = Model::withoutEvents(function () {
            $piece = create(Piece::class);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short'] as $type => $name) {
                $piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            return $piece;
        });
        foreach ([['webapp.pieces.show', $piece, 'piece_id'], ['webapp.composers.show', $piece->composer, 'composer_id']] as $case) {
            $page = $this->get(route($case[0], $case[1]))->assertOk()->assertSee('data-feedback-launcher', false);
            $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'page_context' => $this->contextToken($page), 'device_type' => 'tablet'])->assertCreated();
            $submission = FeedbackSubmission::latest('id')->first();
            $this->assertSame($case[1]->id, $submission->context[$case[2]]);
            $this->assertSame($case[0], $submission->context['route']);
            $this->assertNull($submission->user_id);
        }
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
    }

    public function test_invalid_context_and_tokens_from_other_sessions_or_accounts_are_rejected()
    {
        $page = $this->get(route('webapp.settings'))->assertOk();
        $token = $this->contextToken($page);
        $this->withExceptionHandling();
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'page_context' => $token.'tampered'])->assertUnprocessable();
        $data = json_decode(Crypt::decryptString($token), true);
        $data['session'] = hash('sha256', 'another-session');
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'page_context' => Crypt::encryptString(json_encode($data))])->assertUnprocessable();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'page_context' => $token])->assertUnprocessable();
        foreach ([['page_context' => ['bad']], ['page_context' => str_repeat('x', 4097)], ['device_type' => 'other'], ['device_type' => ['mobile']]] as $bad) {
            $this->postJson(route('webapp.feedback.store'), ['rating' => 'good'] + $bad)->assertUnprocessable();
        }
        $this->assertDatabaseCount('feedback_submissions', 0);
    }

    public function test_legacy_submissions_still_work_without_page_context_or_device_fields()
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0 Safari/537.36 Edg/130.0')
            ->postJson(route('webapp.feedback.store'), ['rating' => 'bad'])->assertCreated();
        $context = FeedbackSubmission::first()->context;
        $this->assertArrayNotHasKey('url', $context);
        $this->assertSame('desktop', $context['device_type']);
        $this->assertSame('Edge', $context['browser']);
        $this->assertSame('Windows', $context['os']);
    }
}
