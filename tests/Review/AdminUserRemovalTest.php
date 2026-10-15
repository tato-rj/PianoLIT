<?php

namespace Tests\Review;

use App\{Admin, Location, Piece, Subscription, User};
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Admin\{RemoveUser, UserRemovalPolicy};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail};
use Illuminate\Support\Str;

class AdminUserRemovalTest extends ReviewTestCase
{
    private function account($attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    private function visit($userId, $visitor)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => $userId, 'visitor_id' => $visitor, 'platform' => 'webapp', 'started_at' => now(), 'last_activity_at' => now()]);
        return $id;
    }

    private function page($visit, $userId, $visitor)
    {
        $id = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $id, 'visit_id' => $visit, 'user_id' => $userId, 'visitor_id' => $visitor, 'platform' => 'webapp', 'url' => 'https://my.example.test/discover', 'opened_at' => now()]);
        return $id;
    }

    private function event($userId, $visit = null, $page = null)
    {
        return DB::table('activity_events')->insertGetId(['event_id' => (string) Str::uuid(), 'user_id' => $userId, 'visit_id' => $visit, 'page_id' => $page,
            'platform' => $visit ? 'webapp' : 'app', 'source' => 'redis', 'type' => 'legacy_request', 'received_at' => now(), 'occurred_at' => now(), 'metadata' => '{}']);
    }

    private function fixture()
    {
        $target = $this->account(); $other = $this->account(); $visitor = (string) Str::uuid();
        $owned = $this->visit($target->id, $visitor);
        $linked = $this->visit(null, $visitor);
        $foreign = $this->visit($other->id, $visitor);
        $anonymous = $this->visit(null, $visitor);
        foreach ([$linked, $foreign] as $visit) DB::table('activity_visit_links')->insert(['visit_id' => $visit, 'user_id' => $target->id, 'reason' => 'login', 'linked_at_utc' => now()]);
        $ownedPage = $this->page($owned, null, $visitor);
        $linkedPage = $this->page($linked, null, $visitor);
        $foreignPage = $this->page($foreign, $other->id, $visitor);
        $anonymousPage = $this->page($anonymous, null, $visitor);
        $orphanPage = $this->page((string) Str::uuid(), $target->id, $visitor);
        $ownedEvents = [$this->event(null, $owned), $this->event(null, null, $ownedPage), $this->event(null, $linked, $linkedPage), $this->event($target->id), $this->event(null, null, $orphanPage)];
        $foreignEvents = [$this->event($other->id, $foreign, $foreignPage), $this->event(null, $anonymous, $anonymousPage), $this->event($other->id)];
        DB::table('logs')->insert([['user_id' => $target->id, 'total' => 5], ['user_id' => $other->id, 'total' => 2]]);

        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $location = Model::withoutEvents(function () use ($target) { return create(Location::class, ['user_id' => $target->id]); });
        $subscription = Model::withoutEvents(function () use ($target) { return create(Subscription::class, ['email' => $target->email]); });
        $list = DB::table('email_lists')->insertGetId(['name' => 'Newsletter', 'description' => 'Preview list']);
        DB::table('email_list_subscription')->insert(['email_list_id' => $list, 'subscription_id' => $subscription->id]);
        $folder = DB::table('favorite_folders')->insertGetId(['user_id' => $target->id, 'name' => 'Private folder']);
        DB::table('favorites')->insert([['user_id' => $target->id, 'piece_id' => $piece->id, 'favorite_folder_id' => $folder], ['user_id' => $other->id, 'piece_id' => $piece->id, 'favorite_folder_id' => null]]);
        DB::table('tutorial_requests')->insert(['user_id' => $target->id, 'piece_id' => $piece->id]);
        DB::table('score_annotations')->insert(['user_id' => $target->id, 'piece_id' => $piece->id, 'score_key' => str_repeat('a', 64), 'marks' => '[]']);
        DB::table('recently_viewed_pieces')->insert(['user_id' => $target->id, 'piece_id' => $piece->id, 'viewed_at' => now()]);
        DB::table('user_auth_providers')->insert(['user_id' => $target->id, 'provider' => 'google', 'provider_user_id' => 'preview-subject', 'provider_email' => $target->email]);
        DB::table('auth_provider_audits')->insert(['user_id' => $target->id, 'action' => 'linked', 'provider' => 'google', 'created_at' => now()]);
        $billing = Stripe::create(['stripe_id' => 'cus_retained', 'plan' => 'monthly', 'status' => 'canceled', 'ended_at' => now()->subDay()]);
        $target->membership()->create(['source_type' => Stripe::class, 'source_id' => $billing->id]);
        DB::table('payments')->insert(['user_id' => $target->id, 'charge_id' => 'retained-charge', 'amount' => 999]);
        return compact('target', 'other', 'owned', 'linked', 'foreign', 'anonymous', 'ownedPage', 'linkedPage', 'foreignPage', 'anonymousPage', 'orphanPage', 'ownedEvents', 'foreignEvents', 'folder', 'subscription', 'location', 'billing');
    }

    private function history()
    {
        return collect(['activity_visits', 'activity_pages', 'activity_events', 'logs'])->mapWithKeys(function ($table) {
            return [$table => DB::table($table)->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all()];
        })->all();
    }

    public static function modes() { return [[false], [true]]; }

    /** @dataProvider modes */
    public function test_removal_cleans_private_data_and_preserves_billing_without_touching_other_accounts($purge)
    {
        $f = $this->fixture(); $before = $this->history();
        $this->actingAs(create(Admin::class), 'admin')->delete(route($purge ? 'admin.users.purge' : 'admin.users.destroy', $f['target']),
            ['confirmation_email' => strtoupper($f['target']->email), 'user_id' => $f['other']->id])->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $f['target']->id]);
        $this->assertDatabaseHas('users', ['id' => $f['other']->id]);
        foreach (['favorite_folders', 'favorites', 'tutorial_requests', 'score_annotations', 'recently_viewed_pieces', 'user_auth_providers'] as $table) $this->assertDatabaseMissing($table, ['user_id' => $f['target']->id]);
        $this->assertDatabaseMissing('subscriptions', ['id' => $f['subscription']->id]);
        $this->assertDatabaseMissing('email_list_subscription', ['subscription_id' => $f['subscription']->id]);
        $this->assertDatabaseMissing('locations', ['id' => $f['location']->id]);
        $this->assertDatabaseHas('favorites', ['user_id' => $f['other']->id]);
        $this->assertDatabaseHas('payments', ['charge_id' => 'retained-charge']);
        $this->assertDatabaseHas('stripe_memberships', ['id' => $f['billing']->id]);
        $this->assertDatabaseHas('memberships', ['user_id' => $f['target']->id, 'source_id' => $f['billing']->id]);
        $this->assertDatabaseHas('auth_provider_audits', ['action' => 'linked', 'user_id' => null]);
        if (!$purge) $this->assertSame($before, $this->history(), 'Delete must retain all visit/page/event rows unchanged');
        else {
            foreach (['owned', 'linked'] as $key) $this->assertDatabaseMissing('activity_visits', ['id' => $f[$key]]);
            foreach (['ownedPage', 'linkedPage', 'orphanPage'] as $key) $this->assertDatabaseMissing('activity_pages', ['id' => $f[$key]]);
            foreach ($f['ownedEvents'] as $id) $this->assertDatabaseMissing('activity_events', compact('id'));
            $this->assertDatabaseMissing('logs', ['user_id' => $f['target']->id]);
        }
        foreach (['foreign', 'anonymous'] as $key) $this->assertDatabaseHas('activity_visits', ['id' => $f[$key]]);
        foreach ($f['foreignEvents'] as $id) $this->assertDatabaseHas('activity_events', compact('id'));
        $this->assertDatabaseHas('logs', ['user_id' => $f['other']->id]);
        $this->assertSame($purge, DB::table('activity_purged_users')->where('user_id', $f['target']->id)->exists());
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    /** @dataProvider modes */
    public function test_a_failed_account_delete_rolls_back_all_history_and_model_hook_cleanup($purge)
    {
        $f = $this->fixture(); $before = $this->history();
        DB::statement("CREATE TRIGGER fail_account_delete BEFORE DELETE ON users BEGIN SELECT RAISE(ABORT, 'forced account failure'); END");
        try { (new RemoveUser)->remove($f['target']->id, $f['target']->email, $purge); $this->fail('Deletion must fail'); }
        catch (\Illuminate\Database\QueryException $error) { $this->assertStringContainsString('forced account failure', $error->getMessage()); }
        $this->assertSame($before, $this->history());
        $this->assertDatabaseCount('activity_purged_users', 0);
        $this->assertDatabaseHas('users', ['id' => $f['target']->id]);
        $this->assertDatabaseHas('subscriptions', ['id' => $f['subscription']->id]);
        $this->assertDatabaseHas('email_list_subscription', ['subscription_id' => $f['subscription']->id]);
        $this->assertDatabaseHas('favorite_folders', ['id' => $f['folder']]);
        $this->assertDatabaseHas('locations', ['id' => $f['location']->id]);
        $this->assertDatabaseHas('activity_visit_links', ['visit_id' => $f['linked']]);
        $this->assertDatabaseHas('favorites', ['user_id' => $f['target']->id]);
        $this->assertDatabaseHas('score_annotations', ['user_id' => $f['target']->id]);
        Mail::assertNothingSent(); Http::assertNothingSent();
    }

    public function test_both_actions_require_admin_csrf_and_the_route_accounts_email()
    {
        $user = $this->account(); $other = $this->account();
        $this->withExceptionHandling();
        foreach (['admin.users.destroy', 'admin.users.purge'] as $name) {
            $this->deleteJson(route($name, $user), ['confirmation_email' => $user->email])->assertUnauthorized();
            $this->actingAs($other, 'web')->deleteJson(route($name, $user), ['confirmation_email' => $user->email])->assertUnauthorized();
        }
        $this->actingAs(create(Admin::class), 'admin');
        foreach (['admin.users.destroy', 'admin.users.purge'] as $name) {
            foreach ([[], ['confirmation_email' => $other->email], ['confirmation_email' => [$user->email]]] as $input) $this->deleteJson(route($name, $user), $input)->assertUnprocessable();
            $this->app['env'] = 'review-csrf';
            $this->withSession(['_token' => 'review-user-delete'])->deleteJson(route($name, $user), ['confirmation_email' => $user->email])->assertStatus(419);
            $this->app['env'] = 'testing';
        }
        $this->assertDatabaseCount('users', 2);
    }

    public function test_active_trial_paused_and_scheduled_stripe_memberships_block_both_endpoints_even_for_super_users()
    {
        $this->withExceptionHandling()->actingAs(create(Admin::class), 'admin');
        foreach ([['status' => 'active'], ['status' => 'trialing'], ['status' => 'active', 'paused_at' => now()], ['status' => 'active', 'membership_ends_at' => now()->addDay()], ['status' => 'past_due']] as $state) {
            $user = $this->account(['super_user' => true]);
            $source = Stripe::create($state + ['stripe_id' => 'cus_blocked', 'plan' => 'monthly', 'renews_at' => now()->subDay()]);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
            foreach (['admin.users.destroy', 'admin.users.purge'] as $name) $this->deleteJson(route($name, $user), ['confirmation_email' => $user->email])->assertUnprocessable()->assertJsonValidationErrors('account_removal');
            $this->assertDatabaseHas('users', ['id' => $user->id]);
        }
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_active_apple_and_grace_periods_block_removal_and_all_memberships_are_checked()
    {
        $user = $this->account();
        $ended = Stripe::create(['stripe_id' => 'cus_ended', 'plan' => 'monthly', 'status' => 'canceled']);
        $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $ended->id]);
        $source = Apple::create(['plan' => 'com.pianolit.monthlyplan', 'latest_receipt' => 'unused', 'password' => 'unused', 'renews_at' => now()->addDay()]);
        $user->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
        $this->withExceptionHandling()->actingAs(create(Admin::class), 'admin');
        foreach (['admin.users.destroy', 'admin.users.purge'] as $name) $this->deleteJson(route($name, $user), ['confirmation_email' => $user->email])->assertUnprocessable();
        $source->update(['renews_at' => now()->subDay()]);
        DB::table('apple_membership_checks')->insert(['apple_membership_id' => $source->id, 'status' => 'grace', 'grace_expires_at' => now()->addDay()]);
        $this->assertNotNull((new UserRemovalPolicy)->reason($user->id));
        DB::table('apple_membership_checks')->where('apple_membership_id', $source->id)->update(['status' => 'expired', 'grace_expires_at' => now()->subMinute()]);
        $this->assertNull((new UserRemovalPolicy)->reason($user->id));
        $this->delete(route('admin.users.destroy', $user), ['confirmation_email' => $user->email])->assertRedirect(route('admin.users.index'));
    }

    public function test_a_subscription_added_after_page_render_and_missing_sources_cannot_bypass_the_guard()
    {
        $user = $this->account();
        $this->actingAs(create(Admin::class), 'admin')->get(route('admin.users.show', $user))->assertOk();
        $active = Stripe::create(['stripe_id' => 'cus_new', 'plan' => 'monthly', 'status' => 'active', 'renews_at' => now()->addMonth()]);
        $membership = $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $active->id]);
        $this->withExceptionHandling()->deleteJson(route('admin.users.purge', $user), ['confirmation_email' => $user->email])->assertUnprocessable()->assertJsonValidationErrors('account_removal');
        $membership->delete();
        $user->membership()->create(['source_type' => Stripe::class, 'source_id' => 987654]);
        $this->withExceptionHandling()->deleteJson(route('admin.users.purge', $user), ['confirmation_email' => $user->email])->assertUnprocessable()->assertJsonValidationErrors('account_removal');
        DB::table('memberships')->where('user_id', $user->id)->update(['source_type' => 'Untrusted\\Model']);
        $this->deleteJson(route('admin.users.destroy', $user), ['confirmation_email' => $user->email])->assertUnprocessable();
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_retired_bulk_endpoint_cannot_bypass_removal_checks()
    {
        $user = $this->account();
        $this->withExceptionHandling()->actingAs(create(Admin::class), 'admin')
            ->deleteJson(route('admin.users.destroy-many'), ['ids' => json_encode([$user->id])])->assertStatus(410);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_stale_mobile_activity_cannot_recreate_purged_logs_and_keeps_its_response_contract()
    {
        $user = $this->account(); $id = $user->id;
        $billing = Stripe::create(['stripe_id' => 'cus_stale', 'plan' => 'monthly', 'status' => 'canceled', 'ended_at' => now()->subDay()]);
        $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $billing->id]);
        (new RemoveUser)->remove($id, $user->email, true);
        $this->getJson(route('api.users.favorites.show', ['user_id' => $id]))->assertOk()->assertExactJson(['User not found']);
        \App\Activity\Actions::paidInvoice(['created' => now()->timestamp, 'data' => ['object' => ['paid' => true, 'amount_paid' => 999, 'id' => 'in_stale', 'subscription' => 'sub_stale', 'customer' => 'cus_stale']]]);
        DB::transaction(function () use ($id) {
            $this->assertSame([], \App\Activity\PurgedUsers::without([['user_id' => $id, 'url' => '/buffered-before-purge']]));
        });
        $this->assertDatabaseMissing('activity_events', ['user_id' => $id]);
        $this->assertDatabaseHas('activity_purged_users', ['user_id' => $id]);
    }
}
