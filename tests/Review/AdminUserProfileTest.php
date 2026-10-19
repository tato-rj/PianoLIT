<?php

namespace Tests\Review;

use App\{Admin, Location, Piece, Subscription, User};
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail};

class AdminUserProfileTest extends ReviewTestCase
{
    private function user($attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    public function test_profile_is_admin_only_and_renders_details_without_fetching_activity_or_secrets()
    {
        $user = $this->user(['first_name' => '<script>alert(1)</script>', 'occupation' => 'teacher', 'experience' => 'advanced', 'locale' => 'en_US']);
        $source = Stripe::create(['stripe_id' => 'cus_profile', 'plan' => 'yearly', 'status' => 'active', 'card_brand' => 'visa', 'card_last_four' => '4242', 'renews_at' => now()->addYear()]);
        $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
        Model::withoutEvents(function () use ($user) { create(Location::class, ['user_id' => $user->id, 'countryName' => 'Portugal', 'cityName' => 'Lisbon']); });
        DB::table('user_auth_providers')->insert(['user_id' => $user->id, 'provider' => 'google', 'provider_user_id' => 'private-provider-subject', 'provider_email' => 'linked@example.test', 'created_at' => now()]);
        $this->withExceptionHandling()->get(route('admin.users.show', $user))->assertRedirect();
        $this->actingAs($user, 'web')->get(route('admin.users.show', $user))->assertRedirect();
        auth('web')->logout();
        $this->actingAs(create(Admin::class), 'admin');
        DB::enableQueryLog(); DB::flushQueryLog();
        $response = $this->get(route('admin.users.show', $user))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Access & membership', false)->assertSee('Lisbon')->assertSee('Google')->assertSee('linked@example.test')
            ->assertSee('Visa ···· 4242')->assertSee('Teacher')->assertSee('Advanced')->assertSee('Account removal is blocked')
            ->assertSee(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits']), false)
            ->assertDontSee('App Logs (')->assertDontSee('load-logs')->assertDontSee('private-provider-subject')->assertDontSee($user->password);
        $sql = implode("\n", array_column(DB::getQueryLog(), 'query')); DB::disableQueryLog();
        $this->assertDoesNotMatchRegularExpression('/\bactivity_(events|visits|pages|visit_links)\b|from ["`]logs["`]/i', $sql);
        $this->assertStringNotContainsString('latest_receipt', $sql);
        $html = $response->getContent();
        $this->assertStringContainsString('action="'.route('admin.users.destroy', $user).'"', $html);
        $this->assertStringContainsString('action="'.route('admin.users.purge', $user).'"', $html);
        $this->assertSame(2, substr_count($html, 'name="confirmation_email"'));
        $this->assertSame(2, substr_count($html, 'data-billing-blocked="1"'));
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_library_lists_are_distinct_paginated_independently_and_use_webapp_piece_links()
    {
        $user = $this->user();
        $pieces = Model::withoutEvents(function () { return create(Piece::class, [], 25); });
        foreach ($pieces as $piece) {
            DB::table('favorites')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'created_at' => now()]);
            DB::table('tutorial_requests')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'created_at' => now()]);
        }
        DB::table('favorites')->insert(['user_id' => $user->id, 'piece_id' => $pieces[24]->id, 'created_at' => now()]);
        $this->actingAs(create(Admin::class), 'admin');
        $first = $this->get(route('admin.users.show', $user))->assertOk();
        $next = $this->get(route('admin.users.show', ['user' => $user->id, 'favorites_page' => 2, 'requests_page' => 3]))->assertOk();
        $this->assertSame(12, $first->viewData('favorites')->count());
        $this->assertSame(25, $first->viewData('favorites')->total());
        $this->assertSame(12, $next->viewData('favorites')->count());
        $this->assertSame(1, $next->viewData('requests')->count());
        $this->assertEmpty(array_intersect($first->viewData('favorites')->pluck('id')->all(), $next->viewData('favorites')->pluck('id')->all()));
        parse_str(parse_url($next->viewData('favorites')->url(1), PHP_URL_QUERY), $parameters);
        $this->assertEquals(['favorites_page' => 1, 'requests_page' => 3], $parameters);
        $this->assertSame('up-favorites', parse_url($next->viewData('favorites')->url(1), PHP_URL_FRAGMENT));
        $first->assertSee('href="'.route('webapp.pieces.show', $pieces[24]->id).'" target="_blank" rel="noopener noreferrer"', false);
        $this->withExceptionHandling()->getJson(route('admin.users.show', ['user' => $user->id, 'favorites_page' => ['bad']]))->assertUnprocessable();
    }

    public function test_apple_metadata_is_safe_and_excluded_accounts_still_have_management_without_dead_analytics_links()
    {
        $user = $this->user(['email' => 'elena.dare@gmail.com', 'super_user' => true]);
        $source = Apple::create(['plan' => 'com.pianolit.monthlyplan', 'latest_receipt' => 'secret-receipt', 'password' => 'secret-shared-password', 'latest_receipt_info' => '{"original_transaction_id":"original-123","is_trial_period":"false"}', 'renews_at' => now()->addMonth()]);
        $user->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
        $response = $this->actingAs(create(Admin::class), 'admin')->get(route('admin.users.show', $user))->assertOk()
            ->assertSee('Super user')->assertSee('Full media access')->assertSee('Apple App Store')->assertSee('original-123')
            ->assertSee('Account removal is blocked')
            ->assertDontSee('secret-receipt')->assertDontSee('secret-shared-password')
            ->assertDontSee(route('admin.analytics.users.profile', ['user' => $user->id]), false);
        $this->assertNull($response->viewData('source')->getRawOriginal('latest_receipt'));
        $this->assertNull($response->viewData('source')->getRawOriginal('password'));
        $this->assertTrue($response->viewData('analyticsExcluded'));
        Http::assertNothingSent();
    }

    public function test_sparse_profiles_and_email_validation_errors_render_without_logs()
    {
        $user = $this->user();
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.users.show', $user))->assertOk()
            ->assertSee('No subscription')->assertSee('No location saved.')->assertSee('No saved pieces yet.')->assertSee('No tutorial requests.');
        $this->withExceptionHandling()->from(route('admin.users.show', $user))->delete(route('admin.users.purge', $user), ['confirmation_email' => 'wrong@example.test'])
            ->assertRedirect(route('admin.users.show', $user))->assertSessionHasErrors('confirmation_email', null, 'purgeAccount');
        $this->get(route('admin.users.show', $user))->assertOk()->assertSee('data-open-on-load', false)->assertSee('wrong@example.test');
    }

    public function test_incomplete_legacy_apple_records_render_without_guessing_trial_dates()
    {
        $user = $this->user();
        $id = DB::table('apple_memberships')->insertGetId(['plan' => null, 'latest_receipt' => 'unused', 'password' => 'unused', 'created_at' => null, 'renews_at' => null]);
        $user->membership()->create(['source_type' => Apple::class, 'source_id' => $id]);
        DB::table('tutorial_requests')->insert(['user_id' => $user->id, 'piece_id' => 999999, 'created_at' => null]);
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.users.show', $user))->assertOk()
            ->assertSee('Apple App Store')->assertSee('Not provided')->assertSee('end date is unknown')->assertDontSee('On trial')->assertSee('Date not recorded');
    }
}
