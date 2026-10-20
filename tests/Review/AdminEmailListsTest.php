<?php

namespace Tests\Review;

use App\{Admin, Composer, EmailList, Piece, Subscription, Tag};
use App\Jobs\SendMassEmails;
use App\Mail\FreePickEmail;
use App\Services\Admin\{EmailListSubscribersTable, SubscriptionsTable};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Mail, Notification, Queue};

class AdminEmailListsTest extends ReviewTestCase
{
    private function admin()
    {
        $this->actingAs(create(Admin::class, ['email' => 'admin@example.test']), 'admin');
    }

    private function list()
    {
        return EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'A new piece each week.']);
    }

    private function piece()
    {
        return Model::withoutEvents(function () {
            $composer = create(Composer::class);
            $piece = create(Piece::class, ['is_free' => true, 'composer_id' => $composer->id]);
            $piece->tags()->attach(create(Tag::class, ['type' => 'level', 'name' => 'beginner']));
            return $piece;
        });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_directory_uses_shared_table_and_separates_preview_from_campaign_send()
    {
        $this->admin(); $list = $this->list(); $this->piece();
        $list->add(Subscription::create(['email' => 'reader@example.test']));
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk()->assertSee('Never sent')
            ->assertDontSee('cdn.datatables.net')->assertDontSee('Create a new list');
        $xpath = $this->xpath($page);
        $this->assertCount(1, $xpath->query('//section[contains(@class,"admin-table")]//table[@id="email-lists"]/tbody/tr'));
        $this->assertCount(0, $xpath->query('//section[contains(@class,"admin-table")]//h2'));
        $this->assertSame(['List', 'Subscribers', 'Last sent', 'Actions'], array_map(function ($heading) { return trim($heading->textContent); }, iterator_to_array($xpath->query('//table[@id="email-lists"]/thead/tr/th'))));
        $this->assertCount(4, $xpath->query('//table[@id="email-lists"]/tbody/tr/td'));
        $this->assertCount(0, $xpath->query('//table[@id="email-lists"]//small | //table[@id="email-lists"]//*[@data-url] | //table[@id="email-lists"]//a[@target="_blank"]'));
        $this->assertCount(1, $xpath->query('//table[@id="email-lists"]/tbody/tr/td[last()]/div/a[normalize-space(.)="Edit"]/following-sibling::button[normalize-space(.)="Send email"]'));
        foreach (['send', 'send-to'] as $action) {
            $this->assertCount(1, $xpath->query('//form[@action="'.route('admin.subscriptions.lists.'.$action, $list).'" and @method="POST"]/input[@name="_token"]'));
        }
        $this->assertCount(1, $xpath->query('//input[@type="email" and @value="admin@example.test" and @autocomplete="email"]'));
        $this->assertCount(1, $xpath->query('//a[@href="'.route('admin.subscriptions.lists.preview', $list).'" and @target="_blank" and @rel="noopener noreferrer"]'));
        $this->assertCount(1, $xpath->query('//*[@id="list-send-'.$list->id.'" and not(ancestor::table)]'));
        $this->assertCount(1, $xpath->query('//*[@id="list-send-'.$list->id.'"]//a[@href="'.route('admin.subscriptions.lists.preview', $list).'"]'));
        Mail::assertNothingSent();
    }

    public function test_missing_piece_and_empty_list_states_render_without_broken_actions()
    {
        $this->admin(); $list = $this->list(); Queue::fake();
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk()->assertSee('Choose a current free pick');
        $this->assertCount(0, $this->xpath($page)->query('//button[contains(@data-bs-target,"list-send")]'));
        $this->assertCount(1, $this->xpath($page)->query('//table[@id="email-lists"]//button[@disabled and normalize-space(.)="Send email"]'));
        $this->post(route('admin.subscriptions.lists.send', $list))->assertSessionHas('error');
        $this->post(route('admin.subscriptions.lists.send-to', $list), ['email' => 'reader@example.test'])->assertSessionHas('error');
        Queue::assertNothingPushed(); Mail::assertNothingSent();
        $list->delete();
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk()->assertSee('No email list yet')->assertSee('Create Freepick list');
    }

    public function test_subscriber_editor_searches_sorts_filters_and_paginates_in_sql_without_loading_membership_collections()
    {
        $this->admin(); $list = $this->list();
        foreach (range(1, 30) as $i) {
            $subscriber = Subscription::create(['email' => sprintf('reader%02d@example.test', $i), 'origin_url' => $i % 2 ? 'Website' : 'Recital', 'created_at' => now()->subMinutes($i)]);
            if ($i % 2) $list->add($subscriber);
        }
        $route = route('admin.subscriptions.lists.edit', $list);
        $page = $this->get($route)->assertOk()->assertSee('Showing 1–12 of 30 results')->assertDontSee('cdn.datatables.net');
        $this->assertCount(12, $this->xpath($page)->query('//table[@id="list-subscribers"]/tbody/tr'));
        $this->assertCount(12, $this->xpath($page)->query('//input[@role="switch" and @name="enabled" and @onchange="this.form.requestSubmit()"]'));
        $this->assertCount(1, $this->xpath($page)->query('//label[@for="list-description"]'));
        $this->assertCount(1, $this->xpath($page)->query('//header//a[@href="'.route('admin.subscriptions.export', ['type' => 'txt', 'list_id' => $list->id]).'" and @target="_blank" and @rel="noopener noreferrer"]'));
        $this->assertCount(1, $this->xpath($page)->query('//header//button[@data-url="'.route('admin.subscriptions.lists.destroy', $list).'"]'));
        $this->assertCount(1, $this->xpath($page)->query('//*[@id="delete-modal"]//input[@name="_method" and @value="DELETE"]'));
        $first = $page->viewData('subscriptions');
        $second = $this->get($route.'?page=2')->assertOk()->viewData('subscriptions');
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        foreach (['subscribed' => 15, 'unsubscribed' => 15, 'all' => 30] as $status => $total) {
            $this->assertSame($total, $this->get($route.'?status='.$status)->assertOk()->viewData('subscriptions')->total());
        }
        $filtered = $this->get($route.'?search=READER30&status=unsubscribed')->assertOk()->viewData('subscriptions');
        $this->assertSame(1, $filtered->total());
        foreach (array_keys(SubscriptionsTable::SORTS) as $sort) $this->get($route.'?sort='.$sort)->assertOk();
        $page = $this->get($route.'?search=reader&status=subscribed&sort=email_desc')->assertOk()->viewData('subscriptions');
        parse_str(parse_url($page->url(2), PHP_URL_QUERY), $parameters);
        $this->assertEquals(['search' => 'reader', 'status' => 'subscribed', 'sort' => 'email_desc', 'page' => 2], $parameters);
        DB::enableQueryLog(); DB::flushQueryLog();
        $data = (new EmailListSubscribersTable)->get(Request::create($route), $list);
        $this->assertCount(2, DB::getQueryLog()); DB::disableQueryLog();
        $this->assertCount(12, $data['subscriptions']);
    }

    public function test_toggle_uses_desired_state_and_export_contains_only_list_members()
    {
        $this->admin(); Notification::fake(); $list = $this->list();
        $subscriber = Subscription::create(['email' => 'on-list@example.test']);
        Subscription::create(['email' => 'off-list@example.test']);
        $route = route('admin.subscriptions.lists.status', $list);
        foreach ([1, 1, 0, 0, 1] as $enabled) {
            $this->patch($route, ['subscriberId' => $subscriber->id, 'enabled' => $enabled])->assertRedirect();
            $this->assertSame($enabled, $list->subscribers()->count());
        }
        $this->get(route('admin.subscriptions.export', ['type' => 'txt', 'list_id' => $list->id]))
            ->assertOk()->assertSee('on-list@example.test')->assertDontSee('off-list@example.test');
        $this->patchJson($route, ['subscriberId' => $subscriber->id])->assertOk()->assertJson(['status' => 'This subscription has been updated.']);
        $this->assertSame(0, $list->subscribers()->count()); Mail::assertNothingSent();
    }

    public function test_admin_guard_csrf_and_invalid_shapes_prevent_sending_or_writing()
    {
        $list = $this->list(); $this->withExceptionHandling(); Queue::fake();
        $this->getJson(route('admin.subscriptions.lists.index'))->assertUnauthorized();
        foreach (['send', 'send-to'] as $action) $this->postJson(route('admin.subscriptions.lists.'.$action, $list))->assertUnauthorized();
        $this->admin();
        foreach (['bad', ['one@example.test'], 'one@example.test,two@example.test'] as $email) {
            $this->postJson(route('admin.subscriptions.lists.send-to', $list), compact('email'))->assertUnprocessable();
        }
        foreach ([['subscriberId' => [1]], ['subscriberId' => 99999], ['subscriberId' => 1, 'enabled' => 'bad']] as $data) {
            $this->patchJson(route('admin.subscriptions.lists.status', $list), $data)->assertUnprocessable();
        }
        foreach ([['status' => ['all']], ['status' => 'bad'], ['per_page' => 13], ['sort' => 'password_asc'], ['search' => ['bad']]] as $data) {
            $this->getJson(route('admin.subscriptions.lists.edit', ['list' => $list] + $data))->assertUnprocessable();
        }
        $this->app['env'] = 'review-csrf';
        foreach (['send', 'send-to'] as $action) {
            $this->withSession(['_token' => 'csrf-test'])->postJson(route('admin.subscriptions.lists.'.$action, $list), ['email' => 'reader@example.test'])->assertStatus(419);
            $this->getJson(route('admin.subscriptions.lists.'.$action, $list))->assertStatus(405);
        }
        $this->app['env'] = 'testing';
        Mail::assertNothingSent(); Queue::assertNothingPushed();
    }

    public function test_only_freepick_can_be_created_or_renamed_and_deleted_templates_cannot_be_dispatched()
    {
        $this->admin(); $this->withExceptionHandling();
        foreach (['Newsletter', 'Latest Tutorials', 'Share Piece', ['Free Pick']] as $name) {
            $this->postJson(route('admin.subscriptions.lists.store'), ['name' => $name, 'description' => 'Description'])->assertUnprocessable();
        }
        $this->post(route('admin.subscriptions.lists.store'), ['name' => EmailList::FREE_PICK, 'description' => 'Weekly picks'])->assertRedirect();
        $list = EmailList::freepick();
        $this->postJson(route('admin.subscriptions.lists.store'), ['name' => EmailList::FREE_PICK, 'description' => 'Duplicate'])->assertUnprocessable();
        $this->patchJson(route('admin.subscriptions.lists.update', $list), ['name' => 'Newsletter', 'description' => 'Description'])->assertUnprocessable();
        $this->assertSame(EmailList::FREE_PICK, $list->fresh()->name);
        foreach (['Newsletter', 'Latest Tutorials'] as $name) {
            $retired = EmailList::create(['name' => $name, 'description' => 'Historical list']);
            $this->getJson(route('admin.subscriptions.lists.preview', $retired))->assertNotFound();
            $this->postJson(route('admin.subscriptions.lists.send', $retired))->assertNotFound();
            $this->postJson(route('admin.subscriptions.lists.send-to', $retired), ['email' => 'reader@example.test'])->assertNotFound();
        }
        $this->get(route('admin.subscriptions.lists.index'))->assertDontSee('Historical list');
    }

    public function test_freepick_preview_and_campaign_queue_the_existing_mail_and_preserve_send_cooldown()
    {
        $this->admin(); $list = $this->list(); $this->piece(); Queue::fake();
        $subscriber = Subscription::create(['email' => 'reader@example.test']); $list->add($subscriber);
        $this->post(route('admin.subscriptions.lists.send-to', $list), ['email' => 'chosen@example.test'])->assertRedirect();
        Mail::assertQueued(FreePickEmail::class, function ($mail) { return $mail->hasTo('chosen@example.test') && $mail->list_id === 'preview' && $mail->subscription === null; });
        $this->assertNull($list->fresh()->last_sent_at);
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $this->get(route('admin.subscriptions.lists.preview', $list))->assertOk()->assertSee('Free');
        Mail::fake();
        $this->post(route('admin.subscriptions.lists.send', $list))->assertRedirect();
        Queue::assertPushed(SendMassEmails::class, 1);
        $this->assertNotNull($list->fresh()->last_sent_at);
        $this->post(route('admin.subscriptions.lists.send', $list))->assertSessionHas('status', 'This list has recently been sent');
        Queue::assertPushed(SendMassEmails::class, 1);
    }

    public function test_preview_for_an_existing_subscriber_renders_without_unsubscribe_links_or_campaign_logs()
    {
        $this->admin(); $list = $this->list(); $this->piece();
        $subscriber = Subscription::create(['email' => 'reader@example.test']); $list->add($subscriber);
        $this->post(route('admin.subscriptions.lists.send-to', $list), ['email' => $subscriber->email])->assertRedirect();
        Mail::assertQueued(FreePickEmail::class, function ($mail) {
            $transport = new \Illuminate\Mail\Transport\ArrayTransport;
            $mailer = new \Illuminate\Mail\Mailer('list-preview-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
            $mailer->alwaysFrom('preview@example.test', 'PianoLIT');
            $mail->send($mailer);
            $this->assertStringNotContainsString('/unsubscribe/', $transport->messages()->last()->getBody());
            return $mail->subscription === null;
        });
        $this->assertDatabaseCount('email_logs', 0); $this->assertDatabaseCount('email_campaign_reports', 0);
    }

    public function test_failed_preview_keeps_the_recipient_and_reports_failure()
    {
        $this->admin(); $list = $this->list(); $this->piece();
        $pending = \Mockery::mock();
        $pending->shouldReceive('queue')->once()->andThrow(new \RuntimeException('mail unavailable'));
        Mail::shouldReceive('to')->with('chosen@example.test')->once()->andReturn($pending);
        $this->post(route('admin.subscriptions.lists.send-to', $list), ['email' => 'chosen@example.test'])
            ->assertSessionHas('error')->assertSessionMissing('status')->assertSessionHas('_old_input.email', 'chosen@example.test');
        $this->assertNull($list->fresh()->last_sent_at);
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $this->assertCount(1, $this->xpath($page)->query('//*[@id="list-send-'.$list->id.'" and contains(@class,"autoshow")]//input[@name="email" and @value="chosen@example.test"]'));
    }

    public function test_list_deletion_rolls_back_membership_on_failure_and_keeps_subscribers_on_success()
    {
        $this->admin(); $list = $this->list();
        $subscriber = Subscription::create(['email' => 'reader@example.test']); $list->add($subscriber);
        DB::unprepared("CREATE TRIGGER fail_list_delete BEFORE DELETE ON email_lists BEGIN SELECT RAISE(ABORT, 'forced list failure'); END");
        try {
            $this->delete(route('admin.subscriptions.lists.destroy', $list));
            $this->fail('Expected list deletion to fail.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertStringContainsString('forced list failure', $exception->getMessage());
        }
        $this->assertTrue($list->has($subscriber->email));
        DB::unprepared('DROP TRIGGER fail_list_delete');
        $this->from(route('admin.subscriptions.lists.edit', $list))->delete(route('admin.subscriptions.lists.destroy', $list))->assertRedirect(route('admin.subscriptions.lists.index'));
        $this->assertDatabaseCount('email_lists', 0); $this->assertDatabaseCount('email_list_subscription', 0);
        $this->assertDatabaseCount('subscriptions', 1);
    }

    public function test_public_signup_and_reactivation_work_with_only_freepick_and_do_not_send_retired_welcome()
    {
        Notification::fake(); $list = $this->list();
        $form = ['email' => 'reader@example.test', 'started_at' => now()->subSeconds(5)->toDateTimeString()];
        $this->post(route('subscriptions.store'), $form)->assertSessionHas('status');
        $this->assertTrue($list->has($form['email'])); Mail::assertNothingSent();
        $this->app['cache']->flush();
        $this->post(route('subscriptions.store'), $form)->assertSessionHas('error');
        $list->subscribers()->detach(); $this->app['cache']->flush();
        $this->post(route('subscriptions.store'), $form)->assertSessionHas('status');
        $this->assertTrue($list->has($form['email'])); $this->assertDatabaseCount('subscriptions', 1);
        $list->delete(); $this->app['cache']->flush();
        $this->post(route('subscriptions.store'), $form)->assertSessionHas('status');
        Mail::assertNothingSent();
    }
}
