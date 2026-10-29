<?php

namespace Tests\Review;

use App\{EmailList, Subscription};
use App\CrashCourse\{CrashCourse, CrashCourseLesson, CrashCourseSubscription, CrashCourseTopic};
use App\Mail\CrashCourseEmail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Mail, Notification, Redis};
use Stevebauman\Location\Facades\Location;

class CrashCoursePagesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
        Notification::fake();
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function course($title = 'Reading music', $attributes = [], $lessons = 3)
    {
        return Model::withoutEvents(function () use ($title, $attributes, $lessons) {
            $course = create(CrashCourse::class, array_merge([
                'title' => $title, 'slug' => str_slug($title), 'description' => 'Explore the ideas behind the music, one short lesson at a time.',
                'published_at' => now(), 'cover_path' => null,
            ], $attributes));
            for ($order = 0; $order < $lessons; $order++) {
                create(CrashCourseLesson::class, ['crash_course_id' => $course->id, 'order' => $order, 'subject' => 'Lesson '.($order + 1)]);
            }
            return $course->fresh();
        });
    }

    private function xpath($response)
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    private function preview($response, $name)
    {
        if ($directory = getenv('CRASHCOURSE_PREVIEW_DIR')) {
            $html = preg_replace('#http://(?:learn\.)?localhost/(css|js|images)/#', 'http://pianolit.test/$1/', $response->getContent());
            $html = str_replace(['href="/css/', 'src="/js/'], ['href="http://pianolit.test/css/', 'src="http://pianolit.test/js/'], $html);
            $html = preg_replace('#http://(?:learn\.)?localhost/storage/preview/course\.webp#', 'http://pianolit.test/images/home/editorial/learn.webp', $html);
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }

    public function test_directory_uses_shared_cards_topic_navigation_and_filter_preserving_pagination()
    {
        $topic = Model::withoutEvents(function () { return create(CrashCourseTopic::class, ['name' => 'Theory', 'slug' => 'theory']); });
        for ($i = 1; $i <= 13; $i++) {
            $course = $this->course('Theory course '.$i, ['created_at' => now()->subMinutes($i)], 1);
            $course->topics()->attach($topic);
        }
        $draft = $this->course('Draft course', ['published_at' => null], 0);
        $draftTopic = Model::withoutEvents(function () { return create(CrashCourseTopic::class, ['name' => 'Draft topic', 'slug' => 'draft-topic']); });
        $draft->topics()->attach($draftTopic);
        $this->course('Other course', [], 1);
        $response = $this->get('http://learn.localhost/crashcourses?topics=theory')->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://learn.localhost"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Course topics"]//a[@aria-current="page" and contains(.,"Theory")]'));
        $this->assertCount(0, $xpath->query('//a[contains(@class,"resource-card")]//h3[contains(.,"Other course")]'));
        $response->assertDontSee('Draft course')->assertDontSee('Draft topic')->assertSee('topics=theory', false)
            ->assertDontSee('class="grid row', false)->assertDontSee('newsletter-invitation-title');
        $second = $this->get('http://learn.localhost/crashcourses?topics=theory&page=2')->assertOk()->assertSee('Theory course 13');
        $this->assertCount(1, $this->xpath($second)->query('//a[contains(@class,"resource-card")]'));
        $this->get('http://learn.localhost/crashcourses?topics=unknown')->assertOk()->assertSee('No courses match these topics');
    }

    public function test_detail_uses_the_public_shell_breadcrumbs_and_labeled_signup_with_existing_guards()
    {
        $course = $this->course('Reading music', ['cover_path' => 'preview/course.webp']);
        $response = $this->get($course->showRoute())->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//h1[contains(.,"Reading music")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://learn.localhost/crashcourses"]'));
        $form = $xpath->query('//form[@id="crashcourse-form" and @method="POST" and @disable-on-submit]')->item(0);
        $this->assertNotNull($form);
        $this->assertSame(route('crashcourses.signup', $course), $form->getAttribute('action'));
        foreach (['_token', 'started_at', 'subscription_name', 'origin_url', 'form_id'] as $name) {
            $this->assertCount(1, $xpath->query('.//input[@name="'.$name.'"]', $form));
        }
        $this->assertCount(1, $xpath->query('.//label[@for="crashcourse-form-email"]', $form));
        $this->assertCount(1, $xpath->query('.//input[@id="crashcourse-form-email" and @type="email" and @autocomplete="email"]', $form));
        $response->assertSee('3 lessons')->assertSee('site-footer')->assertDontSee('cc-hero')->assertDontSee('START FREE COURSE!');
        $this->preview($response, 'course');
    }

    public static function signupOrigins()
    {
        return [['http://learn.localhost'], ['http://localhost']];
    }

    /** @dataProvider signupOrigins */
    public function test_signup_keeps_first_lesson_delivery_duplicate_protection_and_explicit_newsletter_opt_in($origin)
    {
        $course = $this->course();
        $newsletter = Model::withoutEvents(function () { return create(EmailList::class, ['name' => EmailList::NEWSLETTER]); });
        $data = ['first_name' => 'Learner', 'email' => 'learner@example.test', 'started_at' => now()->subSeconds(10)->toDateTimeString(),
            'subscription_name' => '', 'origin_url' => $origin.'/crashcourses/'.$course->slug, 'form_id' => 'crashcourse-form'];
        $url = $origin.'/crashcourses/'.$course->slug.'/signup';
        $this->from($course->showRoute())->post($url, $data)->assertRedirect($course->showRoute())->assertSessionHas('status');
        $subscription = CrashCourseSubscription::firstOrFail();
        $this->assertSame($course->id, $subscription->crash_course_id);
        $this->assertNotNull($subscription->started_at);
        $this->assertSame($course->lessons->first()->id, $subscription->last_sent_lesson_id);
        Mail::assertQueued(CrashCourseEmail::class, function ($mail) use ($course) {
            return $mail->hasTo('learner@example.test') && $mail->lesson->id === $course->lessons->first()->id;
        });
        $this->assertFalse($newsletter->has('learner@example.test'));
        $subscriber = Subscription::byEmail('learner@example.test')->firstOrFail();
        $this->assertSame($data['origin_url'], $subscriber->origin_url);
        $this->assertSame('crashcourse-form', $subscriber->form_id);
        $this->from($course->showRoute())->post($url, $data)->assertRedirect($course->showRoute())->assertSessionHas('error');
        $this->assertDatabaseCount('crash_course_subscriptions', 1);
        Mail::assertQueued(CrashCourseEmail::class, 1);
        Mail::assertNothingSent();
    }

    public function test_validation_keeps_input_and_does_not_subscribe_on_errors_or_honeypot_submissions()
    {
        $course = $this->course();
        $this->withExceptionHandling();
        $url = route('crashcourses.signup', $course);
        $this->from('http://localhost/')->post($url, ['first_name' => 'Learner', 'email' => 'not-an-email', 'started_at' => now()->subSeconds(10)->toDateTimeString()])
            ->assertRedirect($course->showRoute())->assertSessionHasErrors('email', null, 'crashcourse')->assertSessionDoesntHaveErrors('email');
        $response = $this->get($course->showRoute())->assertOk()->assertSee('value="not-an-email"', false);
        $this->assertCount(0, $this->xpath($response)->query('//*[@id="modal-auth"]//*[@aria-invalid="true"]'));
        $this->assertCount(1, $this->xpath($response)->query('//*[@id="crashcourse-form-email" and @aria-invalid="true"]'));
        $this->preview($response, 'validation');
        $legacyPage = 'http://localhost/crashcourses/'.$course->slug;
        $this->from($legacyPage)->post($legacyPage.'/signup', ['first_name' => 'Learner', 'email' => 'not-an-email', 'started_at' => now()->subSeconds(10)->toDateTimeString()])
            ->assertRedirect($legacyPage)->assertSessionHasErrors('email', null, 'crashcourse');
        $this->postJson($url, ['first_name' => 'Learner', 'email' => 'learner@example.test', 'subscription_name' => 'bot', 'started_at' => now()->subSeconds(10)->toDateTimeString()])->assertForbidden();
        $this->assertDatabaseCount('crash_course_subscriptions', 0);
        Mail::assertNothingQueued();
    }

    public function test_directory_default_selection_and_popup_reuse_the_new_components()
    {
        $course = $this->course('Reading music', ['cover_path' => 'preview/course.webp']);
        $this->course('Harmony at the piano');
        $response = $this->get('http://learn.localhost/crashcourses')->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(2, $xpath->query('//a[contains(@class,"resource-card")]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Course topics"]//a[@aria-current="page" and contains(.,"All courses")]'));
        $this->assertCount(1, $xpath->query('//header[contains(@class,"site-header")]//button[@data-view="newsletter-subscription"]'));
        $this->preview($response, 'directory');
        $popup = $this->get('http://learn.localhost/subscriptions/modal?view=crashcourse')->assertOk();
        $popup->assertSee('crashcourse-popup-form-email')->assertSee('Start this free course')->assertSee('action="'.route('crashcourses.signup', $course).'"', false);
        if (getenv('CRASHCOURSE_PREVIEW_DIR')) {
            $shell = $this->get($course->showRoute())->getContent();
            $shell = str_replace('</main>', '<button type="button" class="btn btn-secondary m-4" id="preview-course-popup" data-bs-toggle="modal" data-bs-target="#modal-subscription">Preview course signup</button></main>', $shell);
            $shell = str_replace('</body>', $popup->getContent().'</body>', $shell);
            $this->preview(response($shell), 'popup');
        }
    }
}
