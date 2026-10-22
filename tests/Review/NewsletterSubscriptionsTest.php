<?php

namespace Tests\Review;

use App\{EmailList, Subscription};
use App\Newsletter\Digest;
use App\Blog\Post;
use App\Jobs\{SendNewsletterCampaign, SendNewsletterEmail};
use App\Services\Newsletter\{MonthlyDigest, PublishedContent, Subscribers};
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Mail, Queue, Redis};

class NewsletterSubscriptionsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Individual tests supply release fixtures; real launches must not
        // change the contents of their otherwise empty test months.
        config(['newsletter.releases' => []]);
        $this->travelTo(carbon('2026-11-01 08:00:00'));
        Queue::fake();
        Redis::shouldReceive('get')->andReturn(null);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function list($name = EmailList::NEWSLETTER)
    {
        return EmailList::firstOrCreate(['name' => $name], ['description' => 'Test list']);
    }

    private function subscriber($email = 'reader@example.test', $name = EmailList::NEWSLETTER)
    {
        $subscriber = Subscription::create(['email' => $email]);
        $this->list($name)->add($subscriber);
        return $subscriber;
    }

    private function story($date = '2026-10-20 12:00:00', $title = 'A new piano story')
    {
        return Model::withoutEvents(function () use ($date, $title) {
            return create(Post::class, ['title' => $title, 'published_at' => $date, 'gift_path' => null]);
        });
    }

    private function transport()
    {
        $transport = new \Illuminate\Mail\Transport\ArrayTransport;
        $mailer = new \Illuminate\Mail\Mailer('newsletter-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('sender@example.test', 'PianoLIT');
        $this->app->instance('mailer', $mailer);
        return $transport;
    }

    public function test_blog_modal_uses_the_standard_field_and_monthly_pianolit_copy_on_legacy_and_new_openers()
    {
        foreach (['subscription', 'blog-subscription', 'newsletter-subscription'] as $view) {
            $response = $this->get('http://blog.localhost/subscriptions/modal?view='.$view)->assertOk()
                ->assertSee('Stay in tune with PianoLIT.')->assertSee('one monthly email')
                ->assertSee('only when there’s something new.')->assertSee('Keep me in the loop')
                ->assertSee('action="http://blog.localhost/newsletter/subscribe"', false)
                ->assertSee('autocomplete="email"', false)->assertSee('pianolit-input', false)
                ->assertSee('for="newsletter-subscription-form-email"', false)->assertSee('name="_token"', false)
                ->assertDontSee('input-light', false)->assertDontSee('SUBSCRIBE NOW')->assertDontSee('free pick');
        }
        $this->get('http://localhost/subscriptions/modal?view=subscription')->assertOk()->assertSee('weekly free pick');
    }

    public function test_blog_signup_is_normalized_idempotent_and_only_joins_the_newsletter_list()
    {
        $freePick = $this->list(EmailList::FREE_PICK);
        $other = $this->list('Other updates');
        $payload = ['email' => 'Reader@Example.test', 'started_at' => now()->subSeconds(10)->toDateTimeString()];
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->from('http://blog.localhost')->post('http://blog.localhost/subscribe', $payload)
                ->assertRedirect('http://blog.localhost')->assertSessionHas('status');
        }
        $subscriber = Subscription::where('email', 'reader@example.test')->firstOrFail();
        $this->assertSame([EmailList::NEWSLETTER], $subscriber->lists()->pluck('name')->all());
        $this->assertFalse($freePick->has($subscriber->email));
        $this->assertFalse($other->has($subscriber->email));
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseCount('email_list_subscription', 1);
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Queue::assertNothingPushed();
    }

    public function test_existing_subscribers_keep_other_opt_outs_and_ordinary_signups_do_not_join_newsletter()
    {
        $subscriber = $this->subscriber('existing@example.test', EmailList::FREE_PICK);
        $other = $this->list('Other updates');
        app(Subscribers::class)->subscribe('EXISTING@example.test');
        $newsletter = $this->list();
        $this->assertTrue($newsletter->has($subscriber->email));
        $this->assertTrue($this->list(EmailList::FREE_PICK)->has($subscriber->email));
        $this->assertFalse($other->has($subscriber->email));
        $newsletter->remove($subscriber);
        app(Subscribers::class)->subscribe($subscriber->email);
        $this->assertTrue($newsletter->has($subscriber->email));
        $ordinary = Subscription::createOrActivate((object) ['email' => 'account@example.test'], false);
        $this->assertFalse($newsletter->has($ordinary->email));
        $this->assertTrue($other->has($ordinary->email));
        $this->assertDatabaseCount('subscriptions', 2);
    }

    public function test_already_open_blog_forms_join_newsletter_without_changing_legacy_free_pick_forms()
    {
        $this->list(); $free = $this->list(EmailList::FREE_PICK);
        $payload = ['email' => 'legacy@example.test', 'started_at' => now()->subSeconds(10)->toDateTimeString()];
        $this->from('http://blog.localhost')->post('http://blog.localhost/subscriptions', $payload)->assertRedirect();
        $this->assertTrue($this->list()->has($payload['email']));
        $this->assertFalse($free->has($payload['email']));
        $payload['email'] = 'website@example.test';
        $this->from('http://localhost')->post('http://localhost/subscriptions', $payload)->assertRedirect();
        $this->assertTrue($free->has($payload['email']));
        $this->assertFalse($this->list()->has($payload['email']));
    }

    public function test_signup_keeps_csrf_validation_and_honeypot_checks()
    {
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'blog-token']);
        $this->postJson('http://blog.localhost/subscribe', ['email' => 'reader@example.test'])->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'blog-token')->postJson('http://blog.localhost/subscribe', [
            'email' => 'invalid', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
        ])->assertStatus(422)->assertJsonValidationErrors('email');
        $this->postJson('http://blog.localhost/subscribe', [
            'email' => 'reader@example.test', 'started_at' => 'not-a-date',
        ])->assertStatus(403);
        $this->assertDatabaseCount('subscriptions', 0);
    }

    public function test_shared_newsletter_forms_and_posts_keep_each_public_hubs_host()
    {
        foreach (['localhost', 'blog.localhost', 'learn.localhost', 'shop.localhost', 'games.localhost'] as $index => $host) {
            $this->travel(1)->minutes();
            $origin = 'http://'.$host;
            $this->get($origin.'/subscriptions/modal?view=newsletter-subscription')->assertOk()
                ->assertSee('action="'.$origin.'/newsletter/subscribe"', false)
                ->assertSee('new features, games, tools, stories');
            $this->from($origin)->post($origin.'/newsletter/subscribe', [
                'email' => 'hub'.$index.'@example.test', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
            ])->assertRedirect($origin);
            $this->assertTrue($this->list()->has('hub'.$index.'@example.test'));
        }
        $this->assertDatabaseCount('email_list_subscription', 5);
        Mail::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_automatic_selection_collects_all_existing_hub_publication_sources_and_routes()
    {
        $this->subscriber();
        $this->story();
        $models = [
            \App\CrashCourse\CrashCourse::class => 'http://learn.localhost/crashcourses/',
            \App\Infograph\Infograph::class => 'http://learn.localhost/infographics/',
            \App\Quiz\Quiz::class => 'http://learn.localhost/quizzes/',
            \App\Shop\eBook::class => 'http://shop.localhost/ebooks/',
            \App\Shop\eScore::class => 'http://shop.localhost/escores/',
            \App\Pianist::class => 'http://learn.localhost/great-pianists/',
        ];
        foreach ($models as $model => $url) {
            $date = $model === \App\Pianist::class ? 'created_at' : 'published_at';
            Model::withoutEvents(function () use ($model, $date) {
                create($model, [$date => '2026-10-10 09:00:00']);
                if ($date === 'published_at') create($model, [$date => null]);
                create($model, [$date => '2026-09-20 09:00:00']);
                create($model, [$date => '2026-11-02 09:00:00']);
            });
        }
        $items = app(PublishedContent::class)->forMonth('2026-10');
        $this->assertCount(7, $items);
        foreach ($models as $url) $this->assertTrue($items->contains(function ($item) use ($url) { return strpos($item->url, $url) === 0; }));
        app(MonthlyDigest::class)->queuePreviousMonth();
        $transport = $this->transport();
        (new SendNewsletterEmail(DB::table('newsletter_deliveries')->value('id')))->handle(app(MonthlyDigest::class));
        $this->assertCount(1, $transport->messages());
        $body = $transport->messages()->first()->getBody();
        foreach ($items as $item) $this->assertStringContainsString($item->url, $body);
        $this->assertStringContainsString('Unsubscribe from the newsletter', $body);
    }

    public function test_code_based_features_games_and_tools_are_collected_once_only_after_launch()
    {
        $this->subscriber();
        $tool = ['title' => 'New chord explorer', 'description' => '<p>Find <strong>new</strong> chords.</p>', 'hub' => 'Learn', 'type' => 'Tool', 'route' => 'tools.chord-finder.index', 'published_at' => '2026-10-15 09:00:00'];
        config(['newsletter.releases' => [
            'chord-explorer' => $tool,
            'new-game' => array_merge($tool, ['title' => 'A new game', 'hub' => 'Games', 'route' => 'games.home']),
            'new-feature' => array_merge($tool, ['title' => 'A new feature', 'hub' => 'Web app', 'route' => 'webapp.discover']),
            'old' => array_merge($tool, ['published_at' => '2026-09-20 09:00:00']),
            'future' => array_merge($tool, ['published_at' => '2026-11-02 09:00:00']),
            'unreleased' => array_merge($tool, ['published_at' => null]),
            'removed-route' => array_merge($tool, ['route' => 'removed.game']),
        ]]);
        $items = app(PublishedContent::class)->forMonth('2026-10');
        $this->assertCount(3, $items);
        $this->assertSame('Find new chords.', $items->firstWhere('key', 'release:chord-explorer')->description);
        app(MonthlyDigest::class)->queuePreviousMonth();
        $digest = Digest::firstOrFail();
        $this->assertCount(3, $digest->content_keys);
        config(['newsletter.releases.late' => $tool]);
        $this->assertCount(3, app(MonthlyDigest::class)->items($digest->month, $digest->content_keys));
        config(['newsletter.releases' => []]);
        $transport = $this->transport();
        (new SendNewsletterEmail(DB::table('newsletter_deliveries')->value('id')))->handle(app(MonthlyDigest::class));
        $this->assertCount(0, $transport->messages());
        $this->assertNotNull(DB::table('newsletter_deliveries')->value('skipped_at'));
        $this->assertNull(app(MonthlyDigest::class)->queuePreviousMonth());
        Queue::assertPushed(SendNewsletterCampaign::class, 1);
    }

    public function test_a_non_blog_release_alone_generates_a_newsletter_and_withdrawn_items_are_omitted()
    {
        $this->subscriber();
        $ebook = Model::withoutEvents(function () {
            return create(\App\Shop\eBook::class, ['published_at' => '2026-10-10 09:00:00']);
        });
        $this->assertNotNull(app(MonthlyDigest::class)->queuePreviousMonth());
        $digest = Digest::firstOrFail();
        $this->assertSame(['ebook:'.$ebook->id], $digest->content_keys);
        $story = $this->story();
        $this->assertCount(1, app(MonthlyDigest::class)->items($digest->month, $digest->content_keys));
        $ebook->update(['published_at' => null]);
        $transport = $this->transport();
        (new SendNewsletterEmail(DB::table('newsletter_deliveries')->value('id')))->handle(app(MonthlyDigest::class));
        $this->assertCount(0, $transport->messages());
    }

    public function test_empty_months_drafts_old_stories_and_current_month_stories_never_queue_a_digest()
    {
        $this->subscriber();
        $this->story('2026-09-30 23:59:59', 'Old story');
        $this->story('2026-11-01 00:00:00', 'Current month story');
        $this->story(null, 'Draft story');
        $this->artisan('newsletter:send-monthly', ['--execute' => true])->assertExitCode(0);
        $this->assertDatabaseCount('newsletter_digests', 0);
        $this->assertDatabaseCount('newsletter_deliveries', 0);
        Queue::assertNothingPushed(); Mail::assertNothingSent();
    }

    public function test_previous_calendar_month_is_snapshotted_once_and_preview_is_read_only()
    {
        $subscriber = $this->subscriber();
        $this->subscriber('free@example.test', EmailList::FREE_PICK);
        $first = $this->story('2026-10-01 00:00:00', 'First October story');
        $last = $this->story('2026-10-31 23:59:59', 'Last October story');
        $this->story('2026-09-30 23:59:59', 'September story');
        $this->story('2026-11-01 00:00:00', 'November story');
        $this->story(null, 'Unpublished story');
        $this->artisan('newsletter:send-monthly')->assertExitCode(0);
        Queue::assertNothingPushed(); $this->assertDatabaseCount('newsletter_digests', 0);
        $this->artisan('newsletter:send-monthly', ['--execute' => true])->assertExitCode(0);
        $digest = Digest::firstOrFail();
        $this->assertSame('2026-10', $digest->month);
        $this->assertEqualsCanonicalizing(['post:'.$first->id, 'post:'.$last->id], $digest->content_keys);
        $this->assertNotNull($digest->queued_at);
        $this->assertDatabaseHas('newsletter_deliveries', ['digest_id' => $digest->id, 'subscription_id' => $subscriber->id]);
        $this->assertDatabaseCount('newsletter_deliveries', 1);
        $this->subscriber('late@example.test');
        $this->artisan('newsletter:send-monthly', ['--execute' => true])->assertExitCode(0);
        $this->assertDatabaseCount('newsletter_deliveries', 1);
        Queue::assertPushed(SendNewsletterCampaign::class, 1);
        Queue::assertPushed(SendNewsletterCampaign::class, function ($job) use ($digest) {
            return $job->digestId === $digest->id && $job->connection === 'redis';
        });
    }

    public function test_legacy_duplicate_emails_only_get_one_monthly_delivery()
    {
        $this->subscriber('reader@example.test');
        $this->subscriber('READER@example.test');
        $this->story();
        app(MonthlyDigest::class)->queuePreviousMonth();
        $this->assertDatabaseCount('newsletter_deliveries', 1);
    }

    public function test_legacy_email_casing_is_reused_without_reactivating_other_lists()
    {
        $subscriber = Subscription::create(['email' => 'Existing@Example.test']);
        $free = $this->list(EmailList::FREE_PICK);
        $result = app(Subscribers::class)->subscribe(' existing@EXAMPLE.test ');
        $this->assertSame($subscriber->id, $result->id);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertTrue($this->list()->has($subscriber->email));
        $this->assertFalse($free->has($subscriber->email));
    }

    public function test_campaign_fanout_is_batched_and_retries_do_not_duplicate_sends()
    {
        $this->story(); $list = $this->list();
        foreach (range(1, 501) as $i) $this->subscriber('reader'.$i.'@example.test');
        app(MonthlyDigest::class)->queuePreviousMonth();
        $digest = Digest::firstOrFail();
        (new SendNewsletterCampaign($digest->id))->handle();
        Queue::assertPushed(SendNewsletterEmail::class, 500);
        $ids = DB::table('newsletter_deliveries')->orderBy('id')->pluck('id');
        (new SendNewsletterCampaign($digest->id, $ids[499]))->handle();
        Queue::assertPushed(SendNewsletterEmail::class, 501);
        (new SendNewsletterCampaign($digest->id, $ids[500]))->handle();
        $this->assertNotNull($digest->fresh()->fanned_out_at);
        (new SendNewsletterCampaign($digest->id))->handle();
        Queue::assertPushed(SendNewsletterEmail::class, 501);
    }

    public function test_delivery_sends_content_once_with_a_newsletter_only_unsubscribe_link()
    {
        $subscriber = $this->subscriber(); $story = $this->story();
        app(MonthlyDigest::class)->queuePreviousMonth();
        $transport = $this->transport();
        $id = DB::table('newsletter_deliveries')->value('id');
        $job = new SendNewsletterEmail($id);
        $job->handle(app(MonthlyDigest::class)); $job->handle(app(MonthlyDigest::class));
        $this->assertCount(1, $transport->messages());
        $message = $transport->messages()->first();
        $this->assertSame('PianoLIT news for October 2026', $message->getSubject());
        $this->assertStringContainsString($story->title, $message->getBody());
        $this->assertStringContainsString('http://blog.localhost/'.$story->slug, $message->getBody());
        $this->assertStringContainsString('/unsubscribe/'.$this->list()->id, $message->getBody());
        $this->assertStringNotContainsString('Free Pick', $message->getBody());
        $this->assertNotNull(DB::table('newsletter_deliveries')->where('id', $id)->value('sent_at'));
        $this->assertDatabaseCount('email_logs', 1);
    }

    public function test_opt_out_before_delivery_preserves_other_lists_and_sends_nothing()
    {
        $subscriber = $this->subscriber(); $this->story();
        $free = $this->list(EmailList::FREE_PICK); $free->add($subscriber);
        app(MonthlyDigest::class)->queuePreviousMonth();
        $url = 'http://blog.localhost/subscriptions/'.$subscriber->email.'/unsubscribe/'.$this->list()->id;
        $this->get($url)->assertOk();
        $transport = $this->transport();
        $id = DB::table('newsletter_deliveries')->value('id');
        (new SendNewsletterEmail($id))->handle(app(MonthlyDigest::class));
        $this->assertCount(0, $transport->messages());
        $this->assertTrue($free->has($subscriber->email));
        $this->assertNotNull(DB::table('newsletter_deliveries')->where('id', $id)->value('skipped_at'));
    }

    public function test_withdrawn_stories_never_produce_empty_mail_and_failed_delivery_can_retry()
    {
        $this->subscriber(); $story = $this->story();
        app(MonthlyDigest::class)->queuePreviousMonth();
        $id = DB::table('newsletter_deliveries')->value('id');
        $mailer = \Mockery::mock(\Illuminate\Contracts\Mail\Mailer::class);
        $mailer->shouldReceive('send')->once()->andThrow(new \RuntimeException('Mail transport unavailable'));
        $this->app->instance('mailer', $mailer);
        try {
            (new SendNewsletterEmail($id))->handle(app(MonthlyDigest::class));
            $this->fail('Expected transport failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Mail transport unavailable', $exception->getMessage());
        }
        $this->assertNull(DB::table('newsletter_deliveries')->where('id', $id)->value('sent_at'));
        $story->update(['published_at' => null]);
        $transport = $this->transport();
        (new SendNewsletterEmail($id))->handle(app(MonthlyDigest::class));
        $this->assertCount(0, $transport->messages());
        $this->assertNotNull(DB::table('newsletter_deliveries')->where('id', $id)->value('skipped_at'));
    }

    public function test_excluded_accounts_can_receive_their_opted_in_digest_without_tracking()
    {
        $this->subscriber('arthurvillar@gmail.com'); $this->story();
        app(MonthlyDigest::class)->queuePreviousMonth();
        $transport = $this->transport();
        (new SendNewsletterEmail(DB::table('newsletter_deliveries')->value('id')))->handle(app(MonthlyDigest::class));
        $this->assertCount(1, $transport->messages());
        $this->assertDatabaseCount('email_logs', 0);
        $this->assertDatabaseCount('email_campaign_reports', 0);
    }

    public function test_recipient_history_expires_but_monthly_duplicate_markers_survive()
    {
        $this->subscriber(); $this->story();
        app(MonthlyDigest::class)->queuePreviousMonth();
        $digest = Digest::firstOrFail();
        $digest->update(['created_at' => now()->subMonthsNoOverflow(3)->subSecond()]);
        app(MonthlyDigest::class)->pruneDeliveries();
        $this->assertDatabaseCount('newsletter_deliveries', 0);
        $this->assertDatabaseCount('newsletter_digests', 1);
        $this->assertNull(app(MonthlyDigest::class)->queuePreviousMonth());
        Queue::assertPushed(SendNewsletterCampaign::class, 1);
    }

    public function test_scheduler_runs_once_a_month_on_the_first_at_eight_in_new_york()
    {
        $event = collect(app(Schedule::class)->events())->first(function ($event) {
            return strpos($event->command ?? '', 'newsletter:send-monthly') !== false;
        });
        $this->assertNotNull($event);
        $this->assertSame('0 8 1 * *', $event->expression);
        $this->assertSame('America/New_York', $event->timezone);
        $this->assertStringContainsString('--execute', $event->command);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->runInBackground);
    }
}
