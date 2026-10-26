<?php

namespace Tests\Review;

use App\{EmailList, Subscription, User};
use App\Auth\Google\Accounts;
use App\Mail\ProfileEmailChange;
use App\Services\Profile\CloseAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Hash, Mail, Notification};
use Illuminate\Support\Str;

class ProfilePageTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
        Notification::fake();
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    public function tearDown(): void
    {
        // The production Apple rollback intentionally refuses linked identities.
        DB::table('user_auth_providers')->delete();
        parent::tearDown();
    }

    private function user($attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return create(User::class, $attributes + ['first_name' => 'Jamie', 'last_name' => 'Parker', 'email' => 'jamie@example.test',
                'password' => Hash::make('current-password'), 'origin' => 'webapp']);
        });
    }

    private function list($name = EmailList::FREE_PICK)
    {
        return EmailList::create(['name' => $name, 'description' => 'A new piece to discover, with recordings and sheet music.']);
    }

    public function test_profile_redirects_before_authentication_and_every_named_link_uses_the_main_domain()
    {
        foreach (['my.', 'shop.', 'learn.', 'blog.', 'games.', 'metronome.', 'www.', 'admin.'] as $prefix) {
            foreach (['profile', 'my-profile'] as $path) {
                $this->get('http://'.$prefix.'localhost/'.$path.'?tab=email-preferences&tag=a%2Bb&tag=c')
                    ->assertStatus(301)->assertRedirect('http://localhost/profile?tab=email-preferences&tag=a%2Bb&tag=c');
            }
            foreach (['users.profile', 'webapp.users.profile'] as $name) $this->assertSame('http://localhost/profile', route($name));
        }
        $this->withExceptionHandling()->get('http://localhost/profile')->assertRedirect('http://localhost/login');
        $this->get('http://localhost/my-profile')->assertStatus(301)->assertRedirect('http://localhost/profile');
        config(['app.url' => 'localhost']);
        $this->assertSame('http://localhost/profile', route('users.profile'));
    }

    public function test_profile_renders_personal_details_linked_accounts_and_all_available_list_descriptions()
    {
        $user = $this->user(); $free = $this->list(); $news = $this->list(EmailList::NEWSLETTER); $this->list('Test');
        DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'jamie-google', 'provider_email' => 'jamie.google@example.test']);
        $response = $this->actingAs($user, 'web')->get('http://localhost/profile')->assertOk()
            ->assertSee('Member since')->assertSee('PianoLIT web app')->assertSee('Jamie Parker')
            ->assertSee('jamie.google@example.test')->assertSee('Linked accounts')->assertSee('Free Pick')->assertSee('PianoLIT News')
            ->assertSee($free->description)->assertSee('Unsubscribe from all')->assertSee('Location is approximate')
            ->assertSee('action="http://localhost/profile/details"', false)->assertSee('role="switch"', false)
            ->assertDontSee('Subscribe to Test')->assertDontSee('public-breadcrumb');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        if ($directory = getenv('PROFILE_PREVIEW_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0700, true);
            file_put_contents($directory.'/profile.html', $response->getContent());
        }
    }

    public function test_a_single_list_has_no_bulk_unsubscribe_button_and_missing_subscriber_still_gets_a_switch()
    {
        $this->list();
        $this->actingAs($this->user(), 'web')->get('http://localhost/profile')->assertOk()->assertSee('Subscribe to Free Pick')->assertDontSee('Unsubscribe from all');
    }

    public function test_profile_edits_are_validated_and_always_owned_by_the_web_session()
    {
        $user = $this->user(); $other = $this->user(['email' => 'other@example.test']);
        $this->actingAs($user, 'web')->patch('http://localhost/profile/details', ['first_name' => 'Updated', 'last_name' => '', 'user_id' => $other->id, 'super_user' => true])
            ->assertRedirect('http://localhost/profile#profile');
        $this->assertSame('Updated', $user->fresh()->first_name);
        $this->assertFalse($user->fresh()->super_user);
        $this->assertSame('Jamie', $other->fresh()->first_name);
        $this->withExceptionHandling()->patchJson('http://localhost/profile/details', ['first_name' => ['bad']])->assertUnprocessable();
        $this->patch('http://shop.localhost/profile/details', ['first_name' => 'Bad'])->assertNotFound();
        $this->patchJson(route('users.update', $other), ['first_name' => 'Bad'])->assertForbidden();
    }

    public function test_explicit_list_switches_are_idempotent_scoped_and_independent()
    {
        $user = $this->user(); $other = $this->user(['email' => 'other@example.test']);
        $free = $this->list(); $news = $this->list(EmailList::NEWSLETTER); $internal = $this->list('Test');
        $this->actingAs($user, 'web');
        for ($i = 0; $i < 2; $i++) $this->patchJson(route('users.profile.subscription', $free), ['enabled' => true, 'user_id' => $other->id, 'email' => $other->email])->assertOk()->assertJson(['subscribed' => true]);
        $subscriber = $user->fresh()->subscription;
        $this->assertSame([$free->id], $subscriber->lists()->pluck('email_lists.id')->all());
        $this->assertNull($other->fresh()->subscription);
        $this->patchJson(route('users.profile.subscription', $news), ['enabled' => true])->assertOk();
        $this->assertSame(2, $subscriber->lists()->count());
        for ($i = 0; $i < 2; $i++) $this->patchJson(route('users.profile.subscription', $free), ['enabled' => false])->assertOk()->assertJson(['subscribed' => false]);
        $this->assertSame([$news->id], $subscriber->lists()->pluck('email_lists.id')->all());
        $this->withExceptionHandling()->patchJson(route('users.profile.subscription', $internal), ['enabled' => true])->assertNotFound();
        $this->patchJson(route('users.profile.subscription', $free), ['enabled' => 'oops'])->assertUnprocessable();
        $this->delete(route('users.profile.unsubscribe-all'))->assertRedirect('http://localhost/profile#email-preferences');
        $this->assertSame(0, $subscriber->lists()->count());
        Mail::assertNothingSent();
    }

    public function test_sensitive_changes_require_proof_and_never_flash_passwords()
    {
        $user = $this->user(); $hash = $user->password;
        $this->withExceptionHandling()->actingAs($user, 'web');
        $this->patchJson(route('users.profile.password'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->from('http://localhost/profile')->patch(route('users.profile.password'), ['current_password' => 'wrong-secret', 'password' => 'new-password', 'password_confirmation' => 'new-password'])
            ->assertSessionHasErrors('current_password', null, 'passwordChange')->assertSessionMissing('_old_input.current_password');
        $this->patchJson(route('users.profile.email'), ['email' => 'new@example.test'])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->deleteJson(route('users.profile.destroy'), ['confirmation_email' => $user->email])->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->assertSame($hash, $user->fresh()->password);
        $this->assertDatabaseCount('closed_accounts', 0);
        Mail::assertNothingSent();
    }

    public function test_preferences_include_and_remove_historical_duplicate_subscribers()
    {
        $user = $this->user(); $free = $this->list(); $news = $this->list(EmailList::NEWSLETTER);
        $first = Subscription::create(['email' => $user->email]); $free->add($first);
        $second = Subscription::create(['email' => strtoupper($user->email)]); $news->add($second);
        $page = $this->actingAs($user, 'web')->get(route('users.profile'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($page->getContent());
        $this->assertSame(2, (new \DOMXPath($dom))->query('//input[@role="switch" and @checked]')->length);
        $this->patchJson(route('users.profile.subscription', $news), ['enabled' => false])->assertOk();
        $this->assertSame(0, $second->lists()->count());
        $news->add($second);
        $this->delete(route('users.profile.unsubscribe-all'))->assertRedirect();
        $this->assertSame(0, $first->lists()->count());
        $this->assertSame(0, $second->lists()->count());
    }

    public function test_password_change_rotates_credentials_without_changing_linked_accounts()
    {
        $user = $this->user(); $old = $user->remember_token;
        DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'apple', 'provider_user_id' => 'jamie-apple', 'provider_email' => null]);
        $this->actingAs($user, 'web')->patch(route('users.profile.password'), ['current_password' => 'current-password', 'password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect('http://localhost/profile#security');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->assertNotSame($old, $user->fresh()->remember_token);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $user->id, 'provider' => 'apple']);
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_provider_only_accounts_reuse_the_existing_confirmation_flow()
    {
        $user = $this->user();
        DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'jamie-google', 'provider_email' => $user->email, 'unusable_password_hash' => $user->password]);
        $this->actingAs($user, 'web')->patch(route('users.profile.password'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect('/auth/google/confirm');
        $this->assertSame('profile', session('google_auth.action'));
        app(Accounts::class)->verified(request(), $user);
        $this->patch(route('users.profile.password'), ['password' => 'new-password', 'password_confirmation' => 'new-password'])->assertRedirect('http://localhost/profile#security');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    private function emailLink($user, $email = 'new@example.test')
    {
        $this->actingAs($user, 'web')->patch(route('users.profile.email'), ['email' => $email, 'current_password' => 'current-password'])->assertRedirect('http://localhost/profile#security');
        $link = null;
        Mail::assertSent(ProfileEmailChange::class, function ($mail) use (&$link, $email) { $link = $mail->confirmationUrl; return $mail->hasTo($email); });
        return $link;
    }

    public function test_email_changes_only_commit_after_a_signed_post_from_the_original_account()
    {
        $user = $this->user(); $list = $this->list();
        $subscriber = Subscription::create(['email' => $user->email]); $list->add($subscriber);
        $link = $this->emailLink($user);
        $this->assertSame('jamie@example.test', $user->fresh()->email);
        $this->get($link)->assertOk()->assertSee('new@example.test');
        $this->assertSame('jamie@example.test', $user->fresh()->email);
        $other = $this->user(['email' => 'other@example.test']);
        $this->withExceptionHandling()->actingAs($other, 'web')->post($link)->assertForbidden();
        $this->actingAs($user, 'web')->post($link)->assertRedirect('http://localhost/profile#security');
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('new@example.test', $subscriber->fresh()->email);
        $this->assertSame(1, $subscriber->lists()->count());
        $this->post($link)->assertForbidden();
    }

    public function test_email_confirmation_expires_rejects_tampering_and_is_invalidated_by_password_changes()
    {
        $user = $this->user(); $link = $this->emailLink($user);
        $this->withExceptionHandling()->post($link.'&extra=1')->assertForbidden();
        $this->travel(61)->minutes();
        $this->post($link)->assertForbidden();
        $this->travelBack();
        $user->update(['password' => Hash::make('changed-password')]);
        auth('web')->setUser($user->fresh());
        $this->post($link)->assertForbidden();
        $this->assertSame('jamie@example.test', $user->fresh()->email);
    }

    public function test_confirmed_email_merges_only_saved_preferences_and_rechecks_registration_conflicts()
    {
        $user = $this->user(); $free = $this->list(); $news = $this->list(EmailList::NEWSLETTER); $unused = $this->list('Other updates');
        $old = Subscription::create(['email' => $user->email]); $free->add($old);
        $destination = Subscription::create(['email' => 'new@example.test']); $news->add($destination);
        $link = $this->emailLink($user);
        $this->post($link)->assertRedirect('http://localhost/profile#security');
        $this->assertSame('new@example.test', $old->fresh()->email);
        $this->assertSame([$free->id, $news->id], $old->lists()->orderBy('email_lists.id')->pluck('email_lists.id')->all());
        $this->assertSame(0, $destination->lists()->count());
        $this->assertFalse($unused->has('new@example.test'));

        Mail::fake();
        $link = $this->emailLink($user->fresh(), 'claimed-later@example.test');
        $this->user(['email' => 'claimed-later@example.test']);
        $this->withExceptionHandling()->post($link)->assertSessionHasErrors('email', null, 'emailChange');
        $this->assertSame('new@example.test', $user->fresh()->email);
        $this->assertSame('new@example.test', $destination->fresh()->email);
    }

    public function test_closure_preserves_business_history_removes_login_and_a_repeat_signup_has_a_new_identity()
    {
        $user = $this->user(); $id = $user->id;
        $list = $this->list(); $subscriber = Subscription::create(['email' => $user->email]); $list->add($subscriber);
        $duplicate = Subscription::create(['email' => strtoupper($user->email)]); $list->add($duplicate);
        DB::table('purchases')->insert(['user_id' => $id, 'item_id' => 42, 'item_type' => \App\Merchandise\eBook::class, 'cost' => 999]);
        DB::table('payments')->insert(['user_id' => $id, 'charge_id' => 'retained-charge', 'amount' => 999]);
        DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'user_id' => $id, 'platform' => 'app', 'type' => 'legacy_request', 'source' => 'redis', 'received_at' => now(), 'metadata' => '{}']);
        DB::table('user_auth_providers')->insert(['user_id' => $id, 'provider' => 'google', 'provider_user_id' => 'jamie-google', 'provider_email' => $user->email]);
        $this->actingAs($user, 'web')->withSession(['private-old-session' => 'old'])->delete(route('users.profile.destroy'), ['confirmation_email' => strtoupper($user->email), 'current_password' => 'current-password', 'user_id' => 999])->assertRedirect('http://localhost/');
        $this->assertGuest('web');
        $this->assertNull(session('private-old-session'));
        $this->assertDatabaseMissing('users', ['id' => $id]);
        $this->assertDatabaseMissing('user_auth_providers', ['user_id' => $id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $subscriber->id]);
        $this->assertDatabaseCount('subscriptions', 0);
        $this->assertDatabaseHas('closed_accounts', ['user_id' => $id, 'origin' => 'webapp']);
        foreach (['purchases', 'payments', 'activity_events'] as $table) $this->assertDatabaseHas($table, ['user_id' => $id]);
        $new = $this->user();
        $this->assertNotSame($id, $new->id);
        $this->assertCount(0, $new->purchases);
        $this->assertCount(0, $new->payments);
        $this->assertDatabaseMissing('activity_events', ['user_id' => $new->id]);
        $this->assertNull(app(Accounts::class)->linkFor($new));
        app(Accounts::class)->link($new, ['sub' => 'jamie-google', 'email' => $new->email]);
        $this->assertDatabaseHas('user_auth_providers', ['user_id' => $new->id, 'provider_user_id' => 'jamie-google']);
        $this->getJson(route('api.users.favorites.show', ['user_id' => $id]))->assertOk()->assertExactJson(['User not found']);
        Mail::assertNothingSent();
    }

    public function test_deletion_failure_rolls_back_the_closure_marker_and_subscription_cleanup()
    {
        $user = $this->user(); $list = $this->list(); $subscriber = Subscription::create(['email' => $user->email]); $list->add($subscriber);
        DB::statement("CREATE TRIGGER fail_profile_delete BEFORE DELETE ON users BEGIN SELECT RAISE(ABORT, 'forced deletion failure'); END");
        try { app(CloseAccount::class)->close($user->id, $user->email); $this->fail('Deletion must fail'); }
        catch (\Illuminate\Database\QueryException $error) { $this->assertStringContainsString('forced deletion failure', $error->getMessage()); }
        $this->assertDatabaseCount('closed_accounts', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $subscriber->id]);
    }

    public function test_incorrect_deletion_confirmation_active_billing_and_legacy_endpoints_cannot_bypass_checks()
    {
        $user = $this->user();
        $this->withExceptionHandling()->actingAs($user, 'web')->deleteJson(route('users.profile.destroy'), ['confirmation_email' => 'wrong@example.test', 'current_password' => 'current-password'])->assertUnprocessable();
        $source = \App\Billing\Sources\Stripe::create(['stripe_id' => 'cus_do_not_contact', 'plan' => 'monthly', 'status' => 'active']);
        $user->membership()->create(['source_type' => get_class($source), 'source_id' => $source->id]);
        foreach (['users.profile.destroy' => [], 'users.destroy' => [$user]] as $name => $parameters) {
            $this->deleteJson(route($name, $parameters), ['confirmation_email' => $user->email, 'current_password' => 'current-password'])->assertUnprocessable()->assertJsonValidationErrors('account_removal');
        }
        $this->assertDatabaseCount('closed_accounts', 0);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->get('http://localhost/profile')->assertOk()->assertSee('Manage membership')->assertDontSee('Permanently delete account');
    }

    public function test_profile_mutations_require_session_authentication_and_csrf()
    {
        $user = $this->user();
        $this->withExceptionHandling()->patchJson(route('users.profile.details'), ['first_name' => 'Bad'])->assertUnauthorized();
        $this->actingAs($user, 'web');
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'profile-test-token'])->deleteJson(route('users.profile.destroy'), ['confirmation_email' => $user->email, 'current_password' => 'current-password'])->assertStatus(419);
        $this->app['env'] = 'testing';
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }
}
