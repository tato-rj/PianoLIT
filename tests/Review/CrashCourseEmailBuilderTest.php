<?php

namespace Tests\Review;

use App\Admin;
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription};
use App\Mail\CrashCourseEmail;
use App\Services\CrashCourses\EmailDesign;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\{DB, Mail, Storage};

class CrashCourseEmailBuilderTest extends ReviewTestCase
{
    private $course;
    private $lesson;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->course = Model::withoutEvents(fn () => create(CrashCourse::class, ['title' => 'Reading music', 'slug' => 'reading-music', 'creator_id' => auth('admin')->id()]));
        $this->lesson = $this->course->lessons()->create(['subject' => 'Your first lesson', 'body' => '<p>Hello [first_name],</p><p>Read this original lesson.</p>', 'reading_time' => 1]);
    }

    private function design(): array
    {
        return ['version' => 1, 'settings' => ['background' => '#ffffff', 'color' => '#242933', 'font' => 'serif', 'preheader' => 'Today’s reading exercise'], 'blocks' => [
            ['id' => 'hero-one', 'type' => 'hero', 'title' => 'Reading the staff', 'content' => '<p>Hello [first_name],</p>', 'background' => '#eaf0ff', 'background_image' => 'https://example.test/background.jpg', 'image' => 'https://example.test/hero.jpg', 'alt' => 'A piano score', 'url' => 'https://example.test/watch', 'label' => 'Watch the lesson'],
            ['id' => 'practice', 'type' => 'panel', 'title' => 'Practice activity', 'content' => '<p>Find middle C.</p><p><a href="https://example.test/score">Open the score</a></p>'],
            ['id' => 'columns', 'type' => 'columns', 'content' => '<p>Left column</p>', 'content_right' => '<p>Right column</p>'],
            ['id' => 'image', 'type' => 'image', 'image' => 'https://example.test/notation.png', 'alt' => 'Music notation'],
            ['id' => 'button', 'type' => 'button', 'label' => 'Listen', 'url' => 'https://example.test/audio'],
            ['id' => 'rule', 'type' => 'divider'], ['id' => 'space', 'type' => 'spacer'],
        ]];
    }

    private function url($action, $course = null, $lesson = null)
    {
        return route('admin.crashcourses.lessons.'.$action, ['crashcourse' => $course ?? $this->course, 'lesson' => $lesson ?? $this->lesson]);
    }

    private function payload(?array $design = null, int $revision = 0): array
    {
        return ['subject' => 'Reading music', 'email_design' => json_encode($design ?? $this->design()), 'email_revision' => $revision, 'body' => '<script>not authoritative</script>'];
    }

    public function test_edit_and_create_load_shared_builder_without_rewriting_existing_content()
    {
        foreach (['edit', 'create'] as $action) {
            $this->get($this->url($action))->assertOk()->assertSee('Email builder')->assertSee('Saved blocks')->assertSee('Background image URL')->assertSee('data-email-canvas', false);
        }
        $this->assertSame('<p>Hello [first_name],</p><p>Read this original lesson.</p>', $this->lesson->fresh()->body);
        $this->assertSame(0, DB::table('crash_course_email_designs')->count());
    }

    public function test_save_compiles_server_html_and_retains_separate_design_and_original_json_shape()
    {
        $keys = array_keys($this->lesson->fresh()->toArray());
        $this->patch($this->url('update'), $this->payload())->assertRedirect();
        $lesson = $this->lesson->fresh();
        $saved = app(EmailDesign::class)->saved($lesson);
        $this->assertSame(1, $saved->revision);
        $this->assertCount(7, json_decode($saved->design, true)['blocks']);
        $this->assertStringContainsString('Reading the staff', $lesson->body);
        $this->assertStringNotContainsString('not authoritative', $lesson->body);
        $this->assertGreaterThan(0, $lesson->reading_time);
        $this->assertSame($keys, array_keys($lesson->toArray()));
        $this->get($this->url('edit'))->assertOk()->assertSee('Reading the staff');
    }

    public function test_saved_mail_has_designed_html_readable_text_and_shared_branding_with_escaped_personalization()
    {
        $this->patch($this->url('update'), $this->payload());
        $mail = new CrashCourseEmail($this->lesson->fresh());
        $mail->subscription = new CrashCourseSubscription(['first_name' => '<img src=x onerror=alert(1)>']);
        $mail->build();
        $renderer = clone app(Markdown::class); $renderer->theme($mail->theme);
        $html = (string) $renderer->render($mail->markdown, $mail->buildViewData());
        $text = (string) $renderer->renderText($mail->markdown, $mail->buildViewData());
        foreach (['Reading the staff', 'Watch the lesson', 'Practice activity', 'Right column', 'Cancel this course', 'mailto:contact@pianolit.com'] as $value) {
            $this->assertStringContainsString($value, $html); $this->assertStringContainsString($value, $text);
        }
        $this->assertStringContainsString('https://example.test/watch', $text);
        $this->assertStringContainsString('https://example.test/score', $text);
        $this->assertStringContainsString('background="https://example.test/background.jpg"', $html);
        $this->assertStringContainsString('bgcolor="#eaf0ff"', $html);
        $this->assertStringContainsString('width="600"', $html);
        $this->assertStringContainsString('editorial-logo-plain', $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<table', $text);
        $this->assertStringNotContainsString('data-email-block', $html);
        $legacy = new CrashCourseEmail(new CrashCourseLesson(['subject' => 'Legacy', 'body' => '<p>Original</p>']));
        $legacy->build();
        $this->assertSame('default', $legacy->theme);
        $this->assertSame('emails.crashcourse', $legacy->markdown);
    }

    public function test_unsaved_preview_uses_same_renderer_without_writing_or_sending()
    {
        $preview = $this->postJson($this->url('preview-design'), $this->payload())->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $this->assertStringContainsString('Reading the staff', $preview->json('html'));
        $this->assertStringContainsString('data-email-block="hero-one"', $preview->json('html'));
        $this->assertStringContainsString('Hello Joe', $preview->json('html'));
        $this->assertSame(0, DB::table('crash_course_email_designs')->count());
        $this->assertSame('Your first lesson', $this->lesson->fresh()->subject);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_create_duplicate_and_delete_keep_design_with_owning_lesson()
    {
        $this->post($this->url('store'), $this->payload())->assertRedirect();
        $created = $this->course->lessons()->reorder('id', 'desc')->first();
        $this->assertNotNull(app(EmailDesign::class)->saved($created));
        $this->get($this->url('duplicate', null, $created))->assertRedirect();
        $copy = CrashCourseLesson::orderByDesc('id')->first();
        $this->assertNotEquals($created->id, $copy->id);
        $this->assertSame(app(EmailDesign::class)->saved($created)->design, app(EmailDesign::class)->saved($copy)->design);
        $this->delete($this->url('destroy', null, $copy))->assertRedirect();
        $this->assertDatabaseMissing('crash_course_email_designs', ['lesson_id' => $copy->id]);
        $this->assertDatabaseHas('crash_course_email_designs', ['lesson_id' => $created->id]);
    }

    public function test_stale_and_legacy_forms_cannot_overwrite_a_design_and_failed_validation_retains_input()
    {
        $this->patch($this->url('update'), $this->payload());
        $this->withExceptionHandling();
        $this->patchJson($this->url('update'), $this->payload())->assertUnprocessable()->assertJsonValidationErrors('email_design');
        $this->patchJson($this->url('update'), ['subject' => 'Old form', 'body' => 'Old text'])->assertUnprocessable();
        $this->assertSame('Reading music', $this->lesson->fresh()->subject);
        $payload = $this->payload(null, 1); $payload['subject'] = '';
        $this->from($this->url('edit'))->patch($this->url('update'), $payload)->assertRedirect($this->url('edit'))->assertSessionHasErrors('subject')->assertSessionHasInput('email_design', $payload['email_design']);
        $response = $this->get($this->url('edit'))->assertOk()->assertSee('Reading the staff');
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        $this->assertSame('', (new \DOMXPath($dom))->query('//input[@name="subject"]')[0]->getAttribute('value'));
    }

    public function test_bad_designs_and_unsafe_urls_are_rejected_without_partial_writes()
    {
        $this->withExceptionHandling();
        $designs = [$this->design(), $this->design(), $this->design(), $this->design(), $this->design()];
        $designs[0]['blocks'][0]['type'] = 'script';
        $designs[1]['blocks'][0]['url'] = 'javascript:alert(1)';
        $designs[2]['blocks'][0]['background_image'] = "https://example.test/a');color:red;";
        $designs[3]['blocks'][0]['padding'] = 9000;
        $designs[4]['blocks'][1]['id'] = 'hero-one';
        foreach ($designs as $design) $this->patchJson($this->url('update'), $this->payload($design))->assertUnprocessable()->assertJsonValidationErrors('email_design');
        foreach (['null', '{broken', str_repeat('x', 200001)] as $json) $this->patchJson($this->url('update'), ['subject' => 'Invalid', 'email_design' => $json, 'email_revision' => 0])->assertUnprocessable();
        $this->assertSame(0, DB::table('crash_course_email_designs')->count());
        $this->assertSame('Your first lesson', $this->lesson->fresh()->subject);
    }

    public function test_rich_text_is_sanitized_and_links_and_formatting_survive()
    {
        $design = $this->design();
        $design['blocks'][0]['content'] = '<script>alert(1)</script><p onclick="alert(1)" style="color:#112233;text-align:right">Keep <strong>this</strong></p><iframe src="https://bad.test"></iframe><img src="data:image/svg+xml,bad" onerror="alert(1)"><a href="javascript:alert(1)">Bad</a><a href="https://example.test/good">Good</a>';
        $this->patch($this->url('update'), $this->payload($design));
        $body = $this->lesson->fresh()->body;
        foreach (['<script', 'onclick', '<iframe', 'onerror', 'data:image', 'javascript:'] as $unsafe) $this->assertStringNotContainsString($unsafe, $body);
        $this->assertStringContainsString('<strong>this</strong>', $body);
        $this->assertStringContainsString('https://example.test/good', $body);
        $this->assertStringContainsString('#112233', $body);
    }

    public function test_cross_course_lesson_operations_are_not_allowed()
    {
        $other = Model::withoutEvents(fn () => create(CrashCourse::class, ['title' => 'Another course', 'slug' => 'another-course']));
        $this->withExceptionHandling();
        foreach (['edit', 'preview', 'duplicate', 'send-to'] as $action) $this->get($this->url($action, $other))->assertNotFound();
        $this->patchJson($this->url('update', $other), $this->payload())->assertNotFound();
        $this->delete($this->url('destroy', $other))->assertNotFound();
        $this->assertDatabaseHas('crash_course_lessons', ['id' => $this->lesson->id]);
    }

    public function test_reusable_library_round_trips_independent_blocks_and_safe_uploads()
    {
        $url = route('admin.crashcourses.email-blocks.index');
        $saved = $this->postJson($url, ['name' => 'Practice activity', 'block' => $this->design()['blocks'][1]])->assertCreated();
        $this->getJson($url)->assertOk()->assertJsonPath('blocks.0.name', 'Practice activity');
        $this->patch($this->url('update'), $this->payload());
        $this->deleteJson($url.'/'.$saved->json('id'))->assertNoContent();
        $this->assertStringContainsString('Practice activity', $this->lesson->fresh()->body);
        $image = UploadedFile::fake()->image('score.png', 900, 600);
        $upload = $this->postJson($this->url('images'), ['file' => $image])->assertCreated();
        $this->assertStringContainsString('/storage/crashcourses/emails/'.$this->course->id.'/', $upload->json('location'));
        $this->assertCount(1, Storage::disk('public')->allFiles());
        $this->withExceptionHandling()->postJson($this->url('images'), ['file' => UploadedFile::fake()->create('script.svg', 1, 'image/svg+xml')])->assertUnprocessable();
    }

    public function test_guests_cannot_read_or_write_builder_data()
    {
        auth('admin')->logout(); $this->withExceptionHandling();
        $this->get($this->url('edit'))->assertRedirect(route('admin.login.show'));
        $this->postJson($this->url('preview-design'), $this->payload())->assertUnauthorized();
        $this->getJson(route('admin.crashcourses.email-blocks.index'))->assertUnauthorized();
        $this->postJson($this->url('images'), ['file' => UploadedFile::fake()->image('score.png')])->assertUnauthorized();
    }

    public function test_transaction_rolls_back_lesson_if_design_persistence_fails()
    {
        DB::statement("CREATE TRIGGER reject_design BEFORE INSERT ON crash_course_email_designs BEGIN SELECT RAISE(ABORT, 'test rejection'); END");
        try { $this->patch($this->url('update'), $this->payload()); $this->fail('Expected write rejection'); }
        catch (\Illuminate\Database\QueryException $error) { $this->assertStringContainsString('test rejection', $error->getMessage()); }
        $this->assertSame('Your first lesson', $this->lesson->fresh()->subject);
        $this->assertStringContainsString('original lesson', $this->lesson->fresh()->body);
        $this->assertSame(0, DB::table('crash_course_email_designs')->count());
    }
}
