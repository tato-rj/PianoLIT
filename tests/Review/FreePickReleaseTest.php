<?php

namespace Tests\Review;

use App\{Admin, EmailList, FreePick, Piece, Subscription, Tag};
use App\Jobs\{SendFreePickCampaign, SendFreePickEmail};
use App\Services\{DiscoverRefresh, FreePickRelease, FreePickSchedule};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Queue, Redis};

class FreePickReleaseTest extends ReviewTestCase
{
    private $piece, $list, $writes = [], $cacheUnavailable = false;

    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-20 09:00:00'));
        Queue::fake();
        Redis::shouldReceive('get')->andReturn(null);
        Redis::shouldReceive('set')->andReturnUsing(function ($key, $value) {
            if ($this->cacheUnavailable) throw new \RuntimeException('cache unavailable');
            $this->writes[$key] = $value;
            return true;
        });
        $this->piece = Model::withoutEvents(function () {
            $piece = create(Piece::class, ['cover_path' => 'covers/release.jpg', 'description' => 'Scheduled piece description fixture.']);
            foreach (['level' => 'elementary', 'period' => 'classical', 'length' => 'short'] as $type => $name) {
                $piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            return $piece;
        });
        $this->list = EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'Free picks']);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function schedule($at = '2026-10-21 08:00:00', $email = true)
    {
        return app(FreePickSchedule::class)->schedule($this->piece, $at ? carbon($at) : null, true, $email);
    }

    private function activate($pick)
    {
        $this->travelTo($pick->scheduled_at);
        app(FreePickSchedule::class)->syncHistory();
        app(FreePickRelease::class)->processDue();
        return $pick->fresh();
    }

    private function mailTransport()
    {
        $transport = new \Illuminate\Mail\Transport\ArrayTransport;
        $mailer = new \Illuminate\Mail\Mailer('freepick-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('sender@example.test', 'PianoLIT');
        $this->app->instance('mailer', $mailer);
        return $transport;
    }

    public function test_due_command_refreshes_both_button_prefixes_before_queueing_once()
    {
        $pick = $this->schedule();
        app(FreePickRelease::class)->processDue();
        Queue::assertNothingPushed();
        $this->assertSame([], $this->writes);
        $this->travelTo($pick->scheduled_at);
        $this->artisan('freepicks:sync-history')->assertExitCode(0);
        $this->assertTrue($this->piece->fresh()->is_free);
        $this->assertSame(['app.discover' => 'app.discover-'.now()->timestamp, 'app.explore' => 'app.explore-'.now()->timestamp], $this->writes);
        $pick = $pick->fresh();
        $this->assertNotNull($pick->activated_at);
        $this->assertNotNull($pick->discover_refreshed_at);
        $this->assertNotNull($pick->email_queued_at);
        $this->assertNotNull($pick->release_completed_at);
        Queue::assertPushed(SendFreePickCampaign::class, function ($job) use ($pick) {
            return $job->freePickId === $pick->id && $job->connection === 'redis';
        });
        $this->artisan('freepicks:sync-history')->assertExitCode(0);
        Queue::assertPushed(SendFreePickCampaign::class, 1);
    }

    public function test_refresh_button_uses_the_same_shared_operation()
    {
        app(\App\Http\Controllers\Admin\RedisController::class)->update(app(DiscoverRefresh::class));
        $this->assertSame('app.discover-'.now()->timestamp, $this->writes['app.discover']);
        $this->assertSame('app.explore-'.now()->timestamp, $this->writes['app.explore']);
    }

    public function test_unchecked_future_choice_refreshes_without_email_and_immediate_choice_runs_after_commit()
    {
        $this->post(route('admin.pieces.schedule', $this->piece), ['mode' => 'later', 'scheduled_at' => '2026-10-21T08:00', 'send_email' => '0'])
            ->assertSessionHasNoErrors();
        $pick = $this->activate(FreePick::latest('id')->first());
        $this->assertFalse($pick->send_email);
        $this->assertNotNull($pick->discover_refreshed_at);
        $this->assertNotNull($pick->release_completed_at);
        $this->assertNull($pick->email_queued_at);
        Queue::assertNothingPushed();
        $this->post(route('admin.pieces.schedule', $this->piece), ['mode' => 'now', 'send_email' => '1'])->assertRedirect();
        Queue::assertPushed(SendFreePickCampaign::class, 1);
        $this->assertNotNull(FreePick::latest('id')->first()->email_queued_at);
    }

    public function test_failed_refresh_prevents_email_and_retries_without_changing_the_selection()
    {
        $pick = $this->schedule();
        $this->cacheUnavailable = true;
        $this->activate($pick);
        $this->assertTrue($this->piece->fresh()->is_free);
        $this->assertNull($pick->fresh()->discover_refreshed_at);
        $this->assertNull($pick->fresh()->email_queued_at);
        Queue::assertNothingPushed();
        $this->cacheUnavailable = false;
        $this->assertSame(1, app(FreePickRelease::class)->processDue());
        Queue::assertPushed(SendFreePickCampaign::class, 1);
    }

    public function test_queue_failure_keeps_the_successful_refresh_checkpoint_and_retries()
    {
        $pick = $this->schedule();
        $this->travelTo($pick->scheduled_at);
        app(FreePickSchedule::class)->syncHistory();
        $broken = \Mockery::mock(\Illuminate\Contracts\Queue\Factory::class);
        $broken->shouldReceive('connection')->with('redis')->once()->andThrow(new \RuntimeException('queue unavailable'));
        Queue::swap($broken);
        $this->assertSame(0, app(FreePickRelease::class)->processDue());
        $this->assertNotNull($pick->fresh()->discover_refreshed_at);
        $this->assertNull($pick->fresh()->email_queued_at);
        $this->assertNull($pick->fresh()->release_completed_at);
        $this->cacheUnavailable = true; // A retry must not need to refresh a second time.
        Queue::fake();
        $this->assertSame(1, app(FreePickRelease::class)->processDue());
        Queue::assertPushed(SendFreePickCampaign::class, 1);
    }

    public function test_scheduler_downtime_does_not_announce_superseded_due_picks()
    {
        $old = $this->schedule('2026-10-21 08:00:00');
        $current = $this->schedule('2026-10-22 08:00:00');
        $this->activate($current);
        $this->assertNotNull($old->fresh()->release_completed_at);
        $this->assertNull($old->fresh()->email_queued_at);
        Queue::assertPushed(SendFreePickCampaign::class, 1);
        Queue::assertPushed(SendFreePickCampaign::class, function ($job) use ($current) { return $job->freePickId === $current->id; });
    }

    public function test_a_failed_immediate_selection_never_refreshes_or_queues_email()
    {
        DB::unprepared('CREATE TRIGGER reject_release_history BEFORE UPDATE OF highlighted_at ON pieces BEGIN SELECT RAISE(ABORT, \'history unavailable\'); END');
        try {
            $this->post(route('admin.pieces.schedule', $this->piece), ['mode' => 'now', 'send_email' => '1']);
            $this->fail('Expected history failure to roll back selection.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseCount('free_picks', 0);
            $this->assertSame([], $this->writes);
            Queue::assertNothingPushed();
        } finally { DB::unprepared('DROP TRIGGER reject_release_history'); }
    }

    public function test_a_fast_campaign_worker_retries_until_the_release_is_committed()
    {
        $pick = $this->schedule();
        $this->travelTo($pick->scheduled_at);
        app(FreePickSchedule::class)->syncHistory();
        $pick->update(['discover_refreshed_at' => now('UTC')]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not committed yet');
        (new SendFreePickCampaign($pick->id))->handle();
    }

    public function test_campaign_chunks_only_the_existing_freepick_audience_and_preserves_piece_identity()
    {
        $pick = $this->activate($this->schedule());
        $ids = [];
        foreach (range(1, 501) as $i) {
            $subscriber = Subscription::create(['email' => 'reader'.$i.'@example.test']);
            $this->list->add($subscriber);
            $ids[] = $subscriber->id;
        }
        $unsubscribed = Subscription::create(['email' => 'not-subscribed@example.test']);
        Queue::fake();
        (new SendFreePickCampaign($pick->id))->handle();
        Queue::assertPushed(SendFreePickEmail::class, 500);
        Queue::assertPushed(SendFreePickCampaign::class, function ($job) use ($pick, $ids) {
            return $job->freePickId === $pick->id && $job->afterSubscriberId === $ids[499];
        });
        Queue::assertNotPushed(SendFreePickEmail::class, function ($job) use ($unsubscribed) { return $job->subscriberId === $unsubscribed->id; });
        Queue::fake();
        (new SendFreePickCampaign($pick->id, $ids[499]))->handle();
        Queue::assertPushed(SendFreePickEmail::class, 1);
        Queue::assertPushed(SendFreePickEmail::class, function ($job) use ($ids) { return $job->subscriberId === $ids[500]; });
    }

    public function test_duplicate_delivery_jobs_send_once_with_unsubscribe_links_and_tracking_exclusions()
    {
        $pick = $this->activate($this->schedule());
        $transport = $this->mailTransport();
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        $this->list->add($subscriber);
        $otherPiece = Model::withoutEvents(function () { return create(Piece::class, ['cover_path' => 'other.jpg', 'description' => 'Wrong later pick fixture.']); });
        app(FreePickSchedule::class)->schedule($otherPiece);
        $job = new SendFreePickEmail($pick->id, $subscriber->id);
        $job->handle(); $job->handle();
        $this->assertCount(1, $transport->messages());
        $body = $transport->messages()->first()->getBody();
        $this->assertStringContainsString($this->piece->description, $body);
        $this->assertStringNotContainsString($otherPiece->description, $body);
        $this->assertStringContainsString(route('subscriptions.unsubscribe', [$subscriber, $this->list]), $body);
        $this->assertDatabaseHas('free_pick_email_deliveries', ['free_pick_id' => $pick->id, 'subscription_id' => $subscriber->id, 'sent_at' => now('UTC')->toDateTimeString()]);
        $this->assertDatabaseCount('email_logs', 1);
        $excluded = Subscription::create(['email' => 'arthurvillar@gmail.com']);
        $this->list->add($excluded);
        $excludedJob = new SendFreePickEmail($pick->id, $excluded->id);
        $excludedJob->handle(); $excludedJob->handle();
        $this->assertCount(2, $transport->messages());
        $this->assertDatabaseCount('email_logs', 1);
        $this->assertDatabaseCount('free_pick_email_deliveries', 2);
    }

    public function test_unsubscribing_or_deleting_after_fanout_prevents_delivery()
    {
        $pick = $this->activate($this->schedule());
        $transport = $this->mailTransport();
        $subscriber = Subscription::create(['email' => 'opted-out@example.test']);
        $this->list->add($subscriber);
        $this->list->subscribers()->detach($subscriber);
        (new SendFreePickEmail($pick->id, $subscriber->id))->handle();
        $subscriber->delete();
        (new SendFreePickEmail($pick->id, $subscriber->id))->handle();
        $this->assertCount(0, $transport->messages());
        $this->assertDatabaseCount('free_pick_email_deliveries', 0);
    }

    public function test_failed_mail_delivery_rolls_back_its_marker_and_can_retry()
    {
        $pick = $this->activate($this->schedule());
        $subscriber = Subscription::create(['email' => 'retry@example.test']);
        $this->list->add($subscriber);
        $mailer = \Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class);
        $mailer->shouldReceive('send')->once()->andThrow(new \RuntimeException('mail unavailable'));
        $this->app->instance('mailer', $mailer);
        $job = new SendFreePickEmail($pick->id, $subscriber->id);
        try { $job->handle(); $this->fail('Expected the mail failure.'); }
        catch (\RuntimeException $exception) { $this->assertSame('mail unavailable', $exception->getMessage()); }
        $this->assertDatabaseCount('free_pick_email_deliveries', 0);
        $transport = $this->mailTransport();
        $job->handle();
        $this->assertCount(1, $transport->messages());
    }

    public function test_checkbox_restores_opt_out_and_rejects_invalid_values()
    {
        $this->withExceptionHandling();
        $this->from(route('admin.pieces.index'))->post(route('admin.pieces.schedule', $this->piece), [
            'mode' => 'later', 'scheduled_at' => '2026-10-19T08:00', 'send_email' => '0',
        ])->assertSessionHasErrors('scheduled_at', null, 'freepick');
        $page = $this->get(route('admin.pieces.index'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($page->getContent());
        $this->assertSame(0, (new \DOMXPath($dom))->query('//input[@id="freepick-send-email" and @checked]')->length);
        foreach (['bad', ['1']] as $invalid) {
            $this->postJson(route('admin.pieces.schedule', $this->piece), ['mode' => 'now', 'send_email' => $invalid])
                ->assertUnprocessable()->assertJsonValidationErrors('send_email');
        }
        $this->assertDatabaseCount('free_picks', 0);
        Queue::assertNothingPushed();
    }

    public function test_migration_preserves_existing_bookings_without_enabling_email()
    {
        $migration = new \AddFreePickReleaseActions;
        $migration->down();
        $id = DB::table('free_picks')->insertGetId(['piece_id' => $this->piece->id, 'scheduled_at' => '2026-10-25 12:00:00']);
        $migration->up();
        $pick = FreePick::findOrFail($id);
        $this->assertFalse($pick->send_email);
        $this->assertTrue($pick->refresh_discover);
        $this->assertSame('2026-10-25 12:00:00', $pick->scheduled_at->toDateTimeString());
        $this->activate($pick);
        $this->assertNotNull($pick->fresh()->discover_refreshed_at);
        Queue::assertNothingPushed();
    }
}
