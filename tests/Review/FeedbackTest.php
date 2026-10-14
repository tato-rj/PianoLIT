<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FeedbackTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    public function test_guests_can_submit_every_rating_with_an_optional_message_without_claiming_an_account()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        foreach (['bad', 'not_bad', 'good'] as $rating) {
            $this->postJson(route('webapp.feedback.store'), ['rating' => $rating, 'user_id' => $user->id, 'message' => '  A useful note  '])
                ->assertCreated()->assertExactJson(['saved' => true]);
            $this->assertDatabaseHas('feedback_submissions', ['rating' => $rating, 'user_id' => null, 'message' => 'A useful note']);
        }
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good'])->assertCreated();
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'not_bad', 'message' => '   '])->assertCreated();
        $this->assertSame(2, FeedbackSubmission::whereNull('message')->count());
        $this->assertDatabaseCount('feedback_submissions', 5);
    }

    public function test_membership_and_tracking_exclusions_do_not_prevent_explicit_feedback_and_identity_comes_from_the_web_session()
    {
        $user = Model::withoutEvents(function () { return create(User::class, ['email' => 'arthurvillar@gmail.com']); });
        $this->actingAs($user, 'web');
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'user_id' => 999999, 'message' => 'My own feedback'])
            ->assertCreated();
        $this->assertDatabaseHas('feedback_submissions', ['user_id' => $user->id, 'message' => 'My own feedback']);
        DB::table('users')->where('id', $user->id)->delete();
        $this->assertDatabaseHas('feedback_submissions', ['user_id' => null, 'message' => 'My own feedback']);
    }

    public function test_invalid_ratings_and_messages_are_rejected_without_saving()
    {
        $this->withExceptionHandling();
        foreach ([[], ['rating' => 'great'], ['rating' => ['good']], ['rating' => 'Good']] as $payload) {
            $this->postJson(route('webapp.feedback.store'), $payload)->assertUnprocessable()->assertJsonValidationErrors('rating');
        }
        foreach ([['text' => 'hello'], str_repeat('a', 2001)] as $message) {
            $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', 'message' => $message])
                ->assertUnprocessable()->assertJsonValidationErrors('message');
        }
        $this->assertDatabaseCount('feedback_submissions', 0);
    }

    public function test_feedback_requires_csrf_and_is_rate_limited_for_guests()
    {
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'feedback-csrf']);
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good'])->assertStatus(419);
        $this->assertDatabaseCount('feedback_submissions', 0);
        for ($i = 0; $i < 6; $i++) {
            $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', '_token' => 'feedback-csrf'])->assertCreated();
        }
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good', '_token' => 'feedback-csrf'])->assertStatus(429);
        $this->assertDatabaseCount('feedback_submissions', 6);
    }

    public function test_other_browser_rate_limits_do_not_use_up_the_feedback_allowance()
    {
        $sharedKey = sha1('my.'.config('app.short_url').'|127.0.0.1');
        for ($i = 0; $i < 20; $i++) \Illuminate\Support\Facades\RateLimiter::hit($sharedKey, 60);
        $this->postJson(route('webapp.feedback.store'), ['rating' => 'good'])->assertCreated();
    }

    public function test_only_admin_sessions_can_read_feedback_and_admin_filters_paginate_twelve_with_escaped_messages()
    {
        $this->withExceptionHandling()->getJson(route('admin.feedback.index'))->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class, ['first_name' => 'Feedback', 'last_name' => 'Member']); });
        $this->actingAs($user, 'web')->getJson(route('admin.feedback.index'))->assertUnauthorized();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.feedback.index'))->assertOk()->assertSee('No feedback yet.');
        for ($i = 0; $i < 13; $i++) {
            FeedbackSubmission::create(['rating' => 'good', 'message' => '<script>alert("example")</script>', 'user_id' => $i === 12 ? $user->id : null]);
        }
        FeedbackSubmission::create(['rating' => 'bad', 'message' => '0']);
        $this->get(route('admin.feedback.index', ['rating' => 'bad']))->assertOk()
            ->assertSee('<div class="admin-feedback-message-text">0</div>', false);
        $first = $this->get(route('admin.feedback.index', ['rating' => 'good']))->assertOk()
            ->assertSee('Feedback Member')->assertSee('Guest')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert("example")</script>', false);
        $page = $first->viewData('submissions');
        $this->assertSame(12, $page->perPage()); $this->assertSame(13, $page->total()); $this->assertCount(12, $page);
        $this->assertSame($user->id, $page->first()->user_id);
        $this->assertStringContainsString('rating=good', $page->url(2));
        $second = $this->get(route('admin.feedback.index', ['rating' => 'good', 'page' => 2]))->assertOk()->viewData('submissions');
        $this->assertCount(1, $second);
        $this->assertCount(0, $page->pluck('id')->intersect($second->pluck('id')));
        $this->getJson(route('admin.feedback.index', ['rating' => ['good']]))->assertUnprocessable();
    }

    public function test_the_guest_modal_uses_the_shared_fullscreen_shell_and_session_post_route()
    {
        $html = view('webapp.feedback.modal')->render();
        foreach (['fullscreen-modal', 'modal-fullscreen', 'Close feedback', 'data-feedback-range', 'maxlength="2000"', 'name="_token"', route('webapp.feedback.store')] as $expected) {
            $this->assertStringContainsString($expected, $html);
        }
        $this->assertStringNotContainsString('name="user_id"', $html);
        $route = app('router')->getRoutes()->getByName('webapp.feedback.store');
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertNotContains('auth:web', $route->gatherMiddleware());
        $this->assertSame('my.'.config('app.short_url'), $route->getDomain());
        $admin = app('router')->getRoutes()->getByName('admin.feedback.index');
        $this->assertContains('auth:admin', $admin->gatherMiddleware());
    }

    public function test_admin_deletes_only_the_selected_feedback_and_returns_to_a_valid_filtered_page()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs(create(Admin::class), 'admin');
        for ($i = 0; $i < 13; $i++) FeedbackSubmission::create(['rating' => 'good', 'user_id' => $user->id]);
        $other = FeedbackSubmission::create(['rating' => 'bad', 'message' => 'Keep this feedback']);
        $page = $this->get(route('admin.feedback.index', ['rating' => 'good', 'page' => 2]))->assertOk();
        $target = $page->viewData('submissions')->first();
        $page->assertSee(route('admin.feedback.destroy', ['feedback' => $target, 'rating' => 'good', 'page' => 2]))
            ->assertSee('aria-label="Delete feedback"', false)->assertSee('icon-trash-2', false);

        $this->delete(route('admin.feedback.destroy', ['feedback' => $target, 'rating' => 'good', 'page' => 2]), ['feedback' => $other->id])
            ->assertRedirect(route('admin.feedback.index', ['rating' => 'good']))->assertSessionHas('status', 'Feedback deleted.');
        $this->assertDatabaseMissing('feedback_submissions', ['id' => $target->id]);
        $this->assertDatabaseHas('feedback_submissions', ['id' => $other->id, 'message' => 'Keep this feedback']);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseCount('feedback_submissions', 13);

        for ($i = 0; $i < 13; $i++) FeedbackSubmission::create(['rating' => 'good']);
        $target = FeedbackSubmission::where('rating', 'good')->first();
        $this->delete(route('admin.feedback.destroy', ['feedback' => $target, 'rating' => 'good', 'page' => 2]))
            ->assertRedirect(route('admin.feedback.index', ['rating' => 'good', 'page' => 2]));
    }

    public function test_feedback_deletion_requires_an_admin_session_and_the_form_csrf_token()
    {
        $feedback = FeedbackSubmission::create(['rating' => 'good']);
        $url = route('admin.feedback.destroy', $feedback);
        $this->withExceptionHandling()->deleteJson($url)->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->deleteJson($url)->assertUnauthorized();
        $this->assertDatabaseHas('feedback_submissions', ['id' => $feedback->id]);

        $this->actingAs(create(Admin::class), 'admin');
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'feedback-delete-csrf'])->deleteJson($url)->assertStatus(419);
        $this->assertDatabaseHas('feedback_submissions', ['id' => $feedback->id]);
        $this->post($url, ['_method' => 'DELETE', '_token' => 'feedback-delete-csrf'])
            ->assertRedirect(route('admin.feedback.index'));
        $this->assertDatabaseMissing('feedback_submissions', ['id' => $feedback->id]);
    }

    public function test_missing_feedback_and_invalid_return_filters_do_not_delete_other_submissions()
    {
        $feedback = FeedbackSubmission::create(['rating' => 'not_bad']);
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        $this->deleteJson(route('admin.feedback.destroy', 999999))->assertNotFound();
        foreach ([['rating' => ['good']], ['rating' => 'unknown'], ['page' => -1], ['page' => ['2']]] as $filters) {
            $this->deleteJson(route('admin.feedback.destroy', ['feedback' => $feedback]), $filters)->assertUnprocessable();
        }
        $this->assertDatabaseCount('feedback_submissions', 1);
    }
}
