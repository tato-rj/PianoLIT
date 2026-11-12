<?php

namespace Tests\Review;

use App\Admin;
use App\CrashCourse\{CrashCourse, CrashCourseTopic};
use App\Mail\CrashCourseFeedbackEmail;
use App\Services\Admin\{CrashCoursesTable, CrashCourseTopics};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Mail, Storage};

class AdminCrashCoursesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
    }

    private function course(string $title = 'Reading music', array $attributes = [])
    {
        return Model::withoutEvents(fn () => create(CrashCourse::class, $attributes + [
            'title' => $title, 'slug' => str_slug($title), 'creator_id' => auth('admin')->id(),
            'description' => 'Learn to read music.', 'cover_path' => null,
        ]));
    }

    private function topic(string $name = 'Theory')
    {
        return CrashCourseTopic::create(['name' => $name, 'slug' => str_slug($name), 'creator_id' => auth('admin')->id()]);
    }

    private function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_directory_uses_shared_table_native_pagination_and_compact_topic_modal()
    {
        $topic = $this->topic();
        $this->topic('Technique'); $this->topic('Music history');
        $titles = ['Reading music', 'Understanding rhythm', 'Major and minor keys', 'Intervals', 'Building chords', 'The circle of fifths', 'Piano technique', 'Practicing scales', 'Reading chord symbols', 'Musical form', 'Baroque music', 'Classical music', 'Romantic music'];
        foreach ($titles as $i => $title) {
            $course = $this->course($title, ['created_at' => now()->subDays($i), 'published_at' => $i % 3 ? now() : null]);
            $course->topics()->attach($topic);
        }
        $response = $this->get(route('admin.crashcourses.index'))->assertOk()->assertSee('Create course')->assertSee('Manage topics')
            ->assertSee('Showing 1–12 of 13')->assertDontSee('datatables.min')->assertDontSee('(new DataTable', false);
        $xpath = $this->xpath($response);
        $this->assertCount(12, $xpath->query('//table[@id="crashcourses-table"]/tbody/tr'));
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(0, $xpath->query('//form[@method="POST" and not(.//input[@name="_token"])]'));
        $this->assertCount(24, $xpath->query('//table[@id="crashcourses-table"]//a[@target="_blank" and @rel="noopener noreferrer" and contains(@href,"learn.localhost")]'));
        $this->assertCount(0, $xpath->query('//a[contains(@href,"crashcourses/topics")]'));
        $this->get(route('admin.crashcourses.index', ['page' => 2]))->assertOk()->assertSee('Showing 13–13 of 13');
        $this->get(route('admin.crashcourses.index', ['page' => 99]))->assertOk()->assertSee('No courses on this page');
        $this->get(route('admin.crashcourses.index', ['search' => 'missing']))->assertOk()->assertSee('No matching courses');

        DB::enableQueryLog(); DB::flushQueryLog();
        app(CrashCoursesTable::class)->get(Request::create('/'));
        $this->assertCount(4, DB::getQueryLog(), 'Count, course page with aggregate counts, page topics and modal topics; no per-row subscription queries.');
        DB::disableQueryLog();
        if ($directory = getenv('CRASHCOURSE_ADMIN_PREVIEW_DIR')) {
            foreach (['directory' => [], 'topics' => ['manage' => 'topics'], 'empty' => ['search' => 'missing']] as $name => $query) {
                $html = $this->get(route('admin.crashcourses.index', $query))->assertOk()->getContent();
                $html = str_replace(['http://admin.localhost', 'http://localhost'], 'http://127.0.0.1:8771', $html);
                file_put_contents($directory.'/'.$name.'.html', $html);
            }
        }
        Http::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_sql_search_filters_counts_and_fixed_sorts_are_literal_and_validated()
    {
        $topic = $this->topic('History');
        $first = $this->course('A 100% music', ['published_at' => now()]); $first->topics()->attach($topic);
        $second = $this->course('Z 1000 music');
        DB::table('crash_course_lessons')->insert(['crash_course_id' => $first->id, 'subject' => 'First lesson', 'body' => 'Content', 'reading_time' => 1]);
        foreach ([[], [], ['completed_at' => now()], ['cancelled_at' => now()]] as $attributes) {
            DB::table('crash_course_subscriptions')->insert($attributes + [
                'first_name' => 'Reader', 'email' => 'reader@example.test', 'crash_course_title' => $first->title,
                'subscriber_id' => 1, 'crash_course_id' => $first->id,
            ]);
        }
        $table = app(CrashCoursesTable::class);
        foreach ([['search' => '%'], ['search' => 'history'], ['topic_id' => $topic->id], ['status' => 'published']] as $state) {
            $this->assertSame([$first->id], $table->query($table->filters(Request::create('/', 'GET', $state)))->pluck('crash_courses.id')->all());
        }
        $this->assertSame([$second->id], $table->query($table->filters(Request::create('/', 'GET', ['search' => (string) $second->id])))->pluck('crash_courses.id')->all());
        foreach (['lessons_desc', 'active_desc'] as $sort) {
            $courses = $table->query($table->filters(Request::create('/', 'GET', compact('sort'))))->get();
            $this->assertSame([$first->id, $second->id], $courses->pluck('id')->all());
            $this->assertSame(1, $courses[0]->lessons_count);
            $this->assertSame(2, $courses[0]->directory_active_subscriptions_count);
        }
        foreach (array_keys(CrashCoursesTable::SORTS) as $sort) {
            $this->assertCount(2, $table->query($table->filters(Request::create('/', 'GET', compact('sort'))))->get());
        }
        $this->assertArrayNotHasKey('directory_active_subscriptions_count', $first->fresh()->toArray());
        $this->withExceptionHandling();
        foreach ([['sort' => 'title;drop'], ['per_page' => 1000], ['topic_id' => ['bad']], ['topic_id' => 999999], ['manage' => ['bad']], ['search' => ['bad']]] as $bad) {
            $this->getJson(route('admin.crashcourses.index', $bad))->assertUnprocessable();
        }
    }

    public function test_topic_names_save_with_scoped_validation_and_directory_state()
    {
        $topic = $this->topic();
        $filters = ['search' => 'music', 'sort' => 'title_asc', 'topic_id' => $topic->id, 'page' => 2];
        $url = route('admin.crashcourses.index', $filters + ['manage' => 'topics']);
        $this->post(route('admin.crashcourses.topics.store', $filters), ['name' => '  Technique  '])->assertRedirect($url);
        $created = CrashCourseTopic::where('name', 'Technique')->firstOrFail();
        $this->patch(route('admin.crashcourses.topics.update', ['topic' => $created] + $filters), ['name' => 'Reading'])->assertRedirect($url);
        $this->assertSame('reading', $created->fresh()->slug);
        $this->withExceptionHandling();
        $this->patch(route('admin.crashcourses.topics.update', ['topic' => $created->fresh()] + $filters), ['name' => 'Theory!'])
            ->assertRedirect($url)->assertSessionHasErrors('name', null, 'crashCourseTopic'.$created->id);
        $response = $this->get($url)->assertOk();
        $xpath = $this->xpath($response);
        $this->assertCount(1, $xpath->query('//input[contains(@class,"is-invalid")]'));
        $this->assertCount(1, $xpath->query('//input[@id="crashcourse-topic-'.$created->id.'" and @value="Theory!"]'));
        $this->assertCount(1, $xpath->query('//input[@id="crashcourse-topic-'.$topic->id.'" and @value="Theory"]'));
        $this->assertCount(0, $xpath->query('//a[contains(@class,"admin-table__sort") and (contains(@href,"manage=") or contains(@href,"page="))]'), 'Sorting must not reopen a dismissed modal or retain the previous page.');
        foreach (['', [], '!!!', 'Theory', str_repeat('x', 256)] as $name) {
            $this->postJson(route('admin.crashcourses.topics.store'), compact('name'))->assertUnprocessable()->assertJsonValidationErrors('name');
        }
        $this->assertSame('Reading', $created->fresh()->name);
    }

    public function test_topic_deletion_detaches_only_its_assignments_and_clears_only_its_filter()
    {
        $topic = $this->topic(); $other = $this->topic('Technique');
        $first = $this->course('First course'); $second = $this->course('Second course');
        $first->topics()->attach($other); $second->topics()->attach($topic);
        $this->assertSame([$second->id], $topic->crashcourses->pluck('id')->all());
        $filters = ['search' => 'course', 'topic_id' => $topic->id, 'status' => 'draft', 'page' => 2];
        $this->delete(route('admin.crashcourses.topics.destroy', ['topic' => $topic] + $filters))
            ->assertRedirect(route('admin.crashcourses.index', ['search' => 'course', 'status' => 'draft', 'page' => 2, 'manage' => 'topics']));
        $this->assertDatabaseMissing('crash_course_topics', ['id' => $topic->id]);
        $this->assertDatabaseMissing('crash_course_crash_course_topic', ['topic_id' => $topic->id]);
        $this->assertDatabaseHas('crash_course_crash_course_topic', ['crash_course_id' => $first->id, 'topic_id' => $other->id]);
        $this->assertDatabaseCount('crash_courses', 2);
    }

    public function test_topic_detachment_rolls_back_if_deletion_fails()
    {
        $topic = $this->topic(); $course = $this->course(); $course->topics()->attach($topic);
        CrashCourseTopic::deleting(function () { throw new \RuntimeException('Simulated failure'); });
        try {
            app(CrashCourseTopics::class)->delete(Request::create('/'), $topic);
            $this->fail('Expected deletion failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated failure', $exception->getMessage());
        }
        $this->assertNotNull($topic->fresh());
        $this->assertDatabaseHas('crash_course_crash_course_topic', ['crash_course_id' => $course->id, 'topic_id' => $topic->id]);
    }

    public function test_publication_and_delete_keep_filters_and_clamp_the_return_page()
    {
        $course = $this->course('Published music', ['published_at' => now()->subDay()]);
        $url = route('admin.crashcourses.update-status', ['crashcourse' => $course, 'return_to' => 'directory', 'status' => 'published', 'page' => 2]);
        $published = $course->published_at;
        $this->patch($url, ['published' => '1'])->assertRedirect(route('admin.crashcourses.index', ['status' => 'published']));
        $this->assertEquals($published, $course->fresh()->published_at);
        $this->patch($url, ['published' => '0'])->assertRedirect(route('admin.crashcourses.index', ['status' => 'published']));
        $this->assertNull($course->fresh()->published_at);
        $this->withExceptionHandling()->patchJson($url, ['published' => 'bad'])->assertUnprocessable();
        $this->delete(route('admin.crashcourses.destroy', ['crashcourse' => $course, 'search' => 'music', 'page' => 2]))
            ->assertRedirect(route('admin.crashcourses.index', ['search' => 'music']));
        $this->assertNull($course->fresh());
    }

    public function test_old_topics_link_redirects_editors_keep_state_and_admin_auth_remains_required()
    {
        $filters = ['search' => 'music', 'sort' => 'title_asc', 'page' => 2];
        $this->get(route('admin.crashcourses.topics.index', $filters))->assertRedirect(route('admin.crashcourses.index', $filters + ['manage' => 'topics']));
        $course = $this->course();
        foreach (['create' => [], 'edit' => ['crashcourse' => $course]] as $action => $parameters) {
            $response = $this->get(route('admin.crashcourses.'.$action, $parameters + $filters))->assertOk();
            $this->assertCount(1, $this->xpath($response)->query('//nav[@aria-label="Breadcrumb"]//a[@href="'.route('admin.crashcourses.index', $filters).'"]'));
            $response->assertSee('search=music', false);
        }
        foreach (['admin.crashcourses.index', 'admin.crashcourses.topics.store', 'admin.crashcourses.topics.update', 'admin.crashcourses.topics.destroy'] as $name) {
            $middleware = app('router')->getRoutes()->getByName($name)->gatherMiddleware();
            $this->assertContains('web', $middleware); $this->assertContains('auth:admin', $middleware);
        }
        auth('admin')->logout();
        $this->withExceptionHandling()->get(route('admin.crashcourses.index'))->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.crashcourses.topics.store'), ['name' => 'Blocked'])->assertRedirect(route('admin.login.show'));
    }

    public function test_course_editor_separates_course_details_from_lesson_actions_and_retains_cleared_topics()
    {
        $course = $this->course();
        $topic = $this->topic();
        $course->topics()->attach($topic);
        $lesson = $course->lessons()->create(['subject' => 'Lesson subject', 'body' => 'Lesson text', 'reading_time' => 3, 'order' => 0]);
        $url = route('admin.crashcourses.edit', $course);
        $response = $this->get($url)->assertOk()->assertSee('Course details')->assertSee('0 active subscribers')
            ->assertSee('New lesson')->assertSee('Feedback email');
        $xpath = $this->xpath($response);
        $this->assertCount(0, $xpath->query('//form//form'));
        $this->assertCount(1, $xpath->query('//form[@id="course-editor"]//input[@name="topics[]" and @checked]'));
        $this->assertCount(1, $xpath->query('//table[@id="course-lessons"]/tbody/tr[@data-lesson-id="'.$lesson->id.'"]'));
        $this->assertCount(1, $xpath->query('//*[@id="lesson-'.$lesson->id.'-preview-modal"]//a[@target="_blank" and @href="'.route('admin.crashcourses.lessons.preview', [$course, $lesson]).'"]'));

        $this->withExceptionHandling()->from($url)->patch(route('admin.crashcourses.update', $course), ['title' => '', 'description' => 'Updated description'])
            ->assertRedirect($url)->assertSessionHasErrors('title');
        $xpath = $this->xpath($this->get($url)->assertOk());
        $this->assertCount(0, $xpath->query('//form[@id="course-editor"]//input[@name="topics[]" and @checked]'));
        $this->assertCount(1, $xpath->query('//input[@id="course-title" and @value=""]'));
        $this->assertSame('Updated description', $xpath->query('//textarea[@id="course-description"]')->item(0)->textContent);
        $this->assertSame([$topic->id], $course->fresh()->topics->modelKeys());
    }

    public function test_lesson_reorder_requires_the_complete_course_list_before_saving()
    {
        $course = $this->course();
        $other = $this->course('Another course');
        $attributes = ['subject' => 'Lesson subject', 'body' => 'Lesson text', 'reading_time' => 1];
        $first = $course->lessons()->create($attributes + ['order' => 0]);
        $second = $course->lessons()->create($attributes + ['order' => 1]);
        $foreign = $other->lessons()->create($attributes + ['order' => 0]);
        $url = route('admin.crashcourses.lessons.reorder', $course);

        $this->patchJson($url, ['ids' => [$second->id, $first->id]])->assertOk();
        $this->assertSame([$second->id, $first->id], $course->lessons()->pluck('id')->all());
        $this->withExceptionHandling();
        foreach ([[$first->id, $foreign->id], [$first->id, $first->id], [$first->id], 'invalid'] as $ids) {
            $this->patchJson($url, compact('ids'))->assertUnprocessable();
            $this->assertSame([$second->id, $first->id], $course->lessons()->pluck('id')->all());
        }
        $this->assertEquals(0, $foreign->fresh()->order);
    }

    public function test_feedback_preview_validates_the_recipient_and_sends_a_sample_email()
    {
        $course = $this->course();
        $this->withExceptionHandling()->getJson(route('admin.crashcourses.feedback.send-to', ['crashcourse' => $course, 'email' => 'invalid']))
            ->assertUnprocessable();
        Mail::assertNothingSent();

        $editor = route('admin.crashcourses.edit', $course);
        $this->from($editor)->get(route('admin.crashcourses.feedback.send-to', ['crashcourse' => $course, 'email' => 'preview@example.test']))
            ->assertRedirect($editor);
        Mail::assertSent(CrashCourseFeedbackEmail::class, fn ($message) => $message->hasTo('preview@example.test')
            && $message->crashcourse->is($course) && $message->subscription === null);
    }
}
