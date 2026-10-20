<?php

namespace Tests\Review;

use App\{Admin, EmailList, Subscription};
use App\Jobs\{SendEmail, SendMassEmails};
use App\Mail\EmailListEmail;
use App\Services\Admin\EmailListTemplates;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\{DB, Mail, Queue};

class AdminEmailListCreationTest extends ReviewTestCase
{
    private $viewsPath;
    private $files;
    private $templates;

    public function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem;
        $this->viewsPath = sys_get_temp_dir().'/pianolit-list-creation-'.bin2hex(random_bytes(8));
        $this->files->ensureDirectoryExists($this->viewsPath.'/emails/lists');
        $this->files->copy(resource_path('views/emails/lists/freepick.blade.php'), $this->viewsPath.'/emails/lists/freepick.blade.php');
        $this->templates = new EmailListTemplates($this->files, $this->viewsPath);
        $this->app->instance(EmailListTemplates::class, $this->templates);
        $this->app['view']->addLocation($this->viewsPath);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->viewsPath) $this->files->deleteDirectory($this->viewsPath);
        } finally {
            parent::tearDown();
        }
    }

    private function admin()
    {
        $this->actingAs(create(Admin::class, ['email' => 'admin@example.test']), 'admin');
    }

    private function createList($name = 'Practice Tips', $description = 'Ideas for your next practice session.')
    {
        $this->post(route('admin.subscriptions.lists.store'), compact('name', 'description'))
            ->assertRedirect(route('admin.subscriptions.lists.index'))->assertSessionHas('status');
        return EmailList::where('name', $name)->firstOrFail();
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_create_button_remains_available_and_new_lists_have_a_view_and_working_editor()
    {
        $this->admin();
        EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'Weekly picks']);
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $xpath = $this->xpath($page);
        $this->assertCount(1, $xpath->query('//header[@id="page-title"]//button[@data-bs-target="#add-modal"]'));
        $this->assertCount(1, $xpath->query('//*[@id="add-modal"]//form[@action="'.route('admin.subscriptions.lists.store').'"]//input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//label[@for="list-name"]'));
        $this->assertCount(1, $xpath->query('//input[@id="list-name" and @name="name" and @maxlength="100" and contains(@class,"form-control")]'));

        $list = $this->createList();
        $this->assertFileExists($this->viewsPath.'/emails/lists/practice-tips.blade.php');
        $this->assertSame($this->files->get(resource_path('stubs/email-list.blade.stub')), $this->files->get($this->templates->path($list)));
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk()->assertSee('Practice Tips');
        $this->assertCount(1, $this->xpath($page)->query('//button[@data-bs-target="#list-send-'.$list->id.'"]'));
        $this->get(route('admin.subscriptions.lists.edit', $list))->assertOk();
        $this->patch(route('admin.subscriptions.lists.update', $list), ['name' => $list->name, 'description' => 'Updated practice ideas.'])->assertRedirect();
        $this->assertSame('Updated practice ideas.', $list->fresh()->description);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_custom_list_previews_and_campaigns_use_the_generated_view_without_a_free_pick()
    {
        $this->admin(); Queue::fake();
        $list = $this->createList();
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        $list->add($subscriber);
        $this->patch(route('admin.subscriptions.lists.status', $list), ['subscriberId' => $subscriber->id, 'enabled' => 1])->assertRedirect();
        $this->post(route('admin.subscriptions.lists.send-to', $list), ['email' => 'preview@example.test'])->assertRedirect();
        Mail::assertQueued(EmailListEmail::class, function ($mail) {
            $this->assertNull($mail->subscription);
            $this->assertSame('preview', $mail->list_id);
            $transport = new \Illuminate\Mail\Transport\ArrayTransport;
            $mailer = new \Illuminate\Mail\Mailer('list-preview', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
            $mailer->alwaysFrom('preview@example.test', 'PianoLIT');
            $mail->send($mailer);
            $this->assertStringContainsString('Ideas for your next practice session.', $transport->messages()->last()->getBody());
            $this->assertStringNotContainsString('/unsubscribe/', $transport->messages()->last()->getBody());
            return $mail->hasTo('preview@example.test');
        });
        $this->assertNull($list->fresh()->last_sent_at);
        $this->assertDatabaseCount('email_logs', 0);
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $this->get(route('admin.subscriptions.lists.preview', $list))->assertOk()->assertSee('Practice Tips')->assertSee('Ideas for your next practice session.');
        Mail::fake();
        $this->post(route('admin.subscriptions.lists.send', $list))->assertRedirect();
        Queue::assertPushed(SendMassEmails::class, function ($job) use ($list) { return $job->listId === $list->id; });
        $this->assertNotNull($list->fresh()->last_sent_at);
        $this->post(route('admin.subscriptions.lists.send', $list))->assertSessionHas('status', 'This list has recently been sent');
        Queue::assertPushed(SendMassEmails::class, 1);

        // Exercise the existing per-subscriber job using fake mail only.
        (new SendEmail($list->id, $list->listId(), $subscriber->id, 'Practice this week'))->handle();
        Mail::assertQueued(EmailListEmail::class, function ($mail) use ($list, $subscriber) {
            $this->assertSame('Practice this week', $mail->build()->subject);
            return $mail->list->id === $list->id && $mail->subscription->id === $subscriber->id && $mail->hasTo($subscriber->email);
        });
    }

    public function test_duplicate_names_template_collisions_and_invalid_shapes_do_not_overwrite_files()
    {
        $this->admin(); $this->withExceptionHandling();
        $list = $this->createList();
        $this->files->put($this->templates->path($list), 'Hand-written email content');
        foreach (['Practice Tips', 'Practice-Tips', 'practice tips', '', '!!!', ['Bad'], str_repeat('a', 101), "Practice\r\nTips"] as $name) {
            $this->postJson(route('admin.subscriptions.lists.store'), ['name' => $name, 'description' => 'Description'])->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->files->put($this->viewsPath.'/emails/lists/existing-template.blade.php', 'Existing template');
        $this->postJson(route('admin.subscriptions.lists.store'), ['name' => 'Existing Template', 'description' => 'Description'])->assertUnprocessable();
        $this->assertSame('Existing template', $this->files->get($this->viewsPath.'/emails/lists/existing-template.blade.php'));
        $this->assertSame('Hand-written email content', $this->files->get($this->templates->path($list)));
        $this->assertDatabaseCount('email_lists', 1);
        $this->patchJson(route('admin.subscriptions.lists.update', $list), ['name' => 'Renamed', 'description' => 'Description'])->assertUnprocessable();
        $this->assertSame('Practice Tips', $list->fresh()->name);
        foreach ([['description' => ['Bad']], ['description' => ''], ['description' => str_repeat('a', 256)]] as $invalid) {
            $this->postJson(route('admin.subscriptions.lists.store'), ['name' => 'Other'] + $invalid)->assertUnprocessable();
        }
    }

    public function test_campaign_mail_preserves_list_unsubscribe_links_and_tracking_exclusions()
    {
        $this->admin();
        $list = $this->createList();
        foreach (['reader@example.test', 'arthurvillar@gmail.com', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $email) {
            $subscriber = Subscription::create(compact('email'));
            $transport = new \Illuminate\Mail\Transport\ArrayTransport;
            $mailer = new \Illuminate\Mail\Mailer('list-campaign', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
            $mailer->alwaysFrom('preview@example.test', 'PianoLIT');
            $mail = (new EmailListEmail($list, 'practice-tips.123', $subscriber))->to($email);
            $mail->send($mailer);
            $this->assertStringContainsString(route('subscriptions.unsubscribe', [$subscriber, $list]), $transport->messages()->last()->getBody());
            $this->assertDatabaseCount('email_logs', 1);
        }
        $this->assertDatabaseHas('email_logs', ['sender_id' => $list->id, 'sender_type' => EmailList::class, 'recipient' => 'reader@example.test', 'list_id' => 'practice-tips.123']);
    }

    public function test_names_are_slugged_and_template_content_does_not_embed_submitted_blade_or_html()
    {
        $this->admin();
        $list = $this->createList('Études & Practice', '{{ unsafe }} <script>bad()</script>');
        $this->assertFileExists($this->viewsPath.'/emails/lists/etudes-practice.blade.php');
        $this->assertStringNotContainsString('<script>', $this->files->get($this->templates->path($list)));
        Mail::swap(new \Illuminate\Mail\MailManager($this->app));
        $this->get(route('admin.subscriptions.lists.preview', $list))->assertOk()->assertDontSee('<script>', false)->assertSee('{{ unsafe }}');
    }

    public function test_file_creation_failure_keeps_input_and_does_not_create_a_database_list()
    {
        $this->admin();
        $this->files->put($this->viewsPath.'/blocked', 'This is a file, not a views directory.');
        $this->app->instance(EmailListTemplates::class, new EmailListTemplates($this->files, $this->viewsPath.'/blocked'));
        $this->from(route('admin.subscriptions.lists.index'))->post(route('admin.subscriptions.lists.store'), ['name' => 'Practice Tips', 'description' => 'Practice ideas.'])
            ->assertRedirect(route('admin.subscriptions.lists.index'))->assertSessionHas('error')->assertSessionMissing('status')->assertSessionHas('_old_input.name', 'Practice Tips');
        $this->assertDatabaseCount('email_lists', 0);
        $this->app->instance(EmailListTemplates::class, $this->templates);
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $this->assertCount(1, $this->xpath($page)->query('//*[@id="add-modal" and contains(@class,"autoshow")]//input[@name="name" and @value="Practice Tips"]'));
    }

    public function test_database_failure_removes_only_the_new_file_and_freepick_recreation_preserves_its_template()
    {
        $this->admin();
        DB::unprepared("CREATE TRIGGER fail_list_create BEFORE INSERT ON email_lists BEGIN SELECT RAISE(ABORT, 'forced creation failure'); END");
        $this->post(route('admin.subscriptions.lists.store'), ['name' => 'Practice Tips', 'description' => 'Practice ideas.'])->assertSessionHas('error');
        $this->assertFileDoesNotExist($this->viewsPath.'/emails/lists/practice-tips.blade.php');
        $this->assertDatabaseCount('email_lists', 0);
        $path = $this->viewsPath.'/emails/lists/freepick.blade.php';
        $original = $this->files->get($path);
        $this->post(route('admin.subscriptions.lists.store'), ['name' => EmailList::FREE_PICK, 'description' => 'Weekly picks'])->assertSessionHas('error');
        $this->assertSame($original, $this->files->get($path));
        DB::unprepared('DROP TRIGGER fail_list_create');
        $list = $this->createList(EmailList::FREE_PICK);
        $this->assertSame($original, $this->files->get($path));
        $this->delete(route('admin.subscriptions.lists.destroy', $list))->assertRedirect();
        $this->assertSame($original, $this->files->get($path));
        $this->createList(EmailList::FREE_PICK);
        $this->assertSame($original, $this->files->get($path));
    }

    public function test_creation_requires_admin_and_csrf_and_validation_reopens_only_the_matching_dialog()
    {
        $this->withExceptionHandling();
        $this->postJson(route('admin.subscriptions.lists.store'), ['name' => 'Practice Tips', 'description' => 'Description'])->assertUnauthorized();
        $this->admin();
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'create-token'])->postJson(route('admin.subscriptions.lists.store'), ['name' => 'Practice Tips', 'description' => 'Description'])->assertStatus(419);
        $this->app['env'] = 'testing';
        $list = $this->createList();
        $this->createList('Teaching Ideas');
        $this->from(route('admin.subscriptions.lists.index'))->post(route('admin.subscriptions.lists.send-to', $list), ['email' => 'invalid', 'preview_list_id' => 'untrusted'])->assertSessionHasErrors('email');
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $xpath = $this->xpath($page);
        $this->assertCount(1, $xpath->query('//div[contains(@class,"autoshow")]'));
        $this->assertCount(1, $xpath->query('//*[@id="list-send-'.$list->id.'" and contains(@class,"autoshow")]'));
        $this->withSession(['_old_input' => ['email' => ['bad'], 'preview_list_id' => ['bad']]])->get(route('admin.subscriptions.lists.index'))->assertOk();
        $this->from(route('admin.subscriptions.lists.index'))->post(route('admin.subscriptions.lists.store'), ['name' => ['bad'], 'description' => 'Description'])->assertSessionHasErrors('name');
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $this->assertCount(1, $this->xpath($page)->query('//*[@id="add-modal" and contains(@class,"autoshow")]//input[@id="list-name" and @aria-invalid="true"]'));
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_directory_paginates_lists_in_sql_and_keeps_creation_available_on_each_page()
    {
        $this->admin();
        foreach (range(1, 13) as $i) EmailList::create(['name' => 'List '.$i, 'description' => 'Description']);
        $page = $this->get(route('admin.subscriptions.lists.index'))->assertOk();
        $this->assertSame(12, $page->viewData('lists')->perPage());
        $this->assertSame(13, $page->viewData('lists')->total());
        $this->assertCount(12, $this->xpath($page)->query('//table[@id="email-lists"]/tbody/tr'));
        $page = $this->get(route('admin.subscriptions.lists.index', ['page' => 2]))->assertOk()->assertSee('List 13');
        $this->assertCount(1, $this->xpath($page)->query('//table[@id="email-lists"]/tbody/tr'));
        $this->assertCount(1, $this->xpath($page)->query('//header[@id="page-title"]//button[@data-bs-target="#add-modal"]'));
        $this->withExceptionHandling()->getJson(route('admin.subscriptions.lists.index', ['page' => ['bad']]))->assertUnprocessable();
    }
}
