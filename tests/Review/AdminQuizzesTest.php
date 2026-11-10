<?php

namespace Tests\Review;

use App\Admin;
use App\Quiz\{Quiz, Level, Topic};
use App\Services\Admin\QuizzesTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\{Request, UploadedFile};
use Illuminate\Support\Facades\{DB, Redis, Storage};

class AdminQuizzesTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Storage::fake('public');
        $this->actingAs(create(Admin::class), 'admin');
    }

    protected function quiz(string $title = 'Piano quiz', array $attributes = [])
    {
        $level = Level::first() ?: create(Level::class, ['name' => 'Beginner', 'slug' => 'beginner']);
        return Model::withoutEvents(fn () => create(Quiz::class, $attributes + [
            'title' => $title, 'slug' => str_slug($title), 'creator_id' => auth('admin')->id(),
            'level_id' => $level->id, 'description' => 'Read and answer the questions.', 'cover_path' => null,
            'questions' => serialize([['Q' => 'Which note?', 'A' => ['C[x]', 'D']]]),
        ]));
    }

    protected function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_shared_table_has_native_pagination_and_management_links()
    {
        for ($i = 1; $i <= 13; $i++) $this->quiz('Quiz '.sprintf('%02d', $i));
        $response = $this->get(route('admin.quizzes.index'))->assertOk()
            ->assertSee('Manage audio')->assertSee('Manage topics')->assertSee('Manage images')
            ->assertDontSee('datatables.min.js')->assertDontSee('(new DataTable', false);
        $xpath = $this->xpath($response);
        $this->assertSame(12, $xpath->query('//table[@id="quizzes-table"]/tbody/tr')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Quizzes pagination"]')->length);
        $this->get(route('admin.quizzes.index', ['page' => 2]))->assertOk()->assertSee('Quiz 01');
    }

    public function test_search_filter_and_fixed_sort_use_sql_and_escape_wildcards()
    {
        $topic = create(Topic::class, ['name' => 'Theory', 'slug' => 'theory']);
        $first = $this->quiz('100% Piano', ['published_at' => now()]);
        $first->topics()->attach($topic);
        $this->quiz('Other Piano');
        $table = app(QuizzesTable::class);
        $filters = $table->filters(Request::create('/', 'GET', ['search' => '%', 'topic_id' => $topic->id, 'status' => 'published', 'sort' => 'title_asc']));
        $this->assertSame([$first->id], $table->query($filters)->pluck('id')->all());
        foreach (array_keys(QuizzesTable::SORTS) as $sort) {
            $this->assertCount(2, $table->query($table->filters(Request::create('/', 'GET', ['sort' => $sort])))->get());
        }
        $this->get(route('admin.quizzes.index', ['search' => 'missing']))->assertOk()->assertSee('No matching quizzes');
        $this->withExceptionHandling()->getJson(route('admin.quizzes.index', ['sort' => 'title;drop']))->assertStatus(422);
    }

    public function test_publication_is_explicit_and_returns_to_a_valid_filtered_page()
    {
        $quiz = $this->quiz('Published quiz', ['published_at' => now()->subDay()]);
        $date = $quiz->published_at->toDateTimeString();
        $this->patch(route('admin.quizzes.update-status', ['quiz' => $quiz]), ['return_to' => 'directory', 'published' => '1'])->assertRedirect(route('admin.quizzes.index'));
        $this->assertSame($date, $quiz->fresh()->published_at->toDateTimeString());
        $this->patch(route('admin.quizzes.update-status', ['quiz' => $quiz, 'status' => 'published', 'page' => 2]), ['return_to' => 'directory', 'published' => '0'])
            ->assertRedirect(route('admin.quizzes.index', ['status' => 'published']));
        $this->assertNull($quiz->fresh()->published_at);
    }

    public function test_draft_previews_are_disabled_while_editor_links_remain_available()
    {
        $draft = $this->quiz('Draft quiz');
        $published = $this->quiz('Published quiz', ['published_at' => now(), 'created_at' => now()->subDay()]);
        $index = $this->get(route('admin.quizzes.index'))->assertOk();
        $xpath = $this->xpath($index);
        $this->assertSame(0, $xpath->query('//a[@href="'.route('quizzes.show', $draft).'"]')->length);
        $this->assertSame(1, $xpath->query('//button[@aria-label="Preview Draft quiz" and @disabled and @aria-disabled="true"]')->length);
        $this->assertSame(2, $xpath->query('//a[@href="'.route('admin.quizzes.edit', $draft).'"]')->length, 'Both the title and Edit open the draft editor.');
        $this->assertSame(1, $xpath->query('//input[@aria-label="Published: Draft quiz" and not(@disabled)]')->length);
        $this->assertSame(1, $xpath->query('//a[@href="'.route('quizzes.show', $published).'" and @target="_blank" and @rel="noopener noreferrer"]')->length);
        $editor = $this->get(route('admin.quizzes.edit', $draft))->assertOk();
        $this->assertSame(1, $this->xpath($editor)->query('//*[@id="page-title"]//button[@disabled and @aria-disabled="true"]')->length);
        $legacy = view('admin.pages.quizzes.table.actions', ['item' => $draft])->render();
        $this->assertStringNotContainsString(route('quizzes.show', $draft), $legacy);
        $this->assertStringContainsString('aria-disabled="true"', $legacy);

        if ($file = getenv('QUIZ_DRAFT_PREVIEW_FILE')) {
            file_put_contents($file, str_replace(['http://admin.localhost', 'http://localhost'], 'http://admin.pianolit.test', $index->getContent()));
        }
        $url = route('admin.quizzes.update-status', $draft);
        $this->patch($url, ['return_to' => 'directory', 'published' => '1'])->assertRedirect(route('admin.quizzes.index'));
        $index = $this->get(route('admin.quizzes.index'))->assertOk();
        $this->assertSame(1, $this->xpath($index)->query('//a[@href="'.route('quizzes.show', $draft).'"]')->length);
        $editor = $this->get(route('admin.quizzes.edit', $draft))->assertOk();
        $this->assertSame(1, $this->xpath($editor)->query('//*[@id="page-title"]//a[@href="'.route('quizzes.show', $draft).'" and @rel="noopener noreferrer"]')->length);
        $this->patch($url, ['return_to' => 'directory', 'published' => '0'])->assertRedirect(route('admin.quizzes.index'));
        $index = $this->get(route('admin.quizzes.index'))->assertOk();
        $this->assertSame(0, $this->xpath($index)->query('//a[@href="'.route('quizzes.show', $draft).'"]')->length);
    }

    public function test_topic_writes_reopen_modal_and_keep_validation_scoped()
    {
        $this->post(route('admin.quizzes.topics.store', ['search' => 'Piano']), ['name' => '  Theory  '])
            ->assertRedirect(route('admin.quizzes.index', ['search' => 'Piano', 'manage' => 'topics']));
        $topic = Topic::where('name', 'Theory')->firstOrFail();
        $other = create(Topic::class, ['name' => 'Harmony', 'slug' => 'harmony']);
        $quiz = $this->quiz();
        $quiz->topics()->attach($topic);
        $this->withExceptionHandling()->post(route('admin.quizzes.topics.store'), ['name' => 'Theory'])
            ->assertSessionHasErrors('name', null, 'quizTopicCreate')->assertRedirect(route('admin.quizzes.index', ['manage' => 'topics']));
        $response = $this->get(route('admin.quizzes.index', ['manage' => 'topics']))->assertOk();
        $this->assertSame(2, $this->xpath($response)->query('//input[@name="name" and @value="Theory"]')->length, 'The create field retains its failed value and the saved row retains its own name.');
        $this->assertSame(1, $this->xpath($response)->query('//input[@name="name" and @value="Harmony"]')->length);
        $this->patch(route('admin.quizzes.topics.update', $topic), ['name' => 'Reading'])->assertRedirect();
        $this->delete(route('admin.quizzes.topics.destroy', ['topic' => $topic->fresh()]))->assertRedirect();
        $this->assertDatabaseMissing('quiz_quiz_topic', ['topic_id' => $topic->id]);
        $this->assertDatabaseHas('quizzes', ['id' => $quiz->id]);
    }

    public function test_legacy_management_pages_redirect_to_the_directory_modals()
    {
        foreach (['admin.quizzes.topics.index' => 'topics', 'admin.quizzes.media.audio' => 'audio', 'admin.quizzes.media.images' => 'images'] as $route => $manage) {
            $this->get(route($route, ['search' => 'Piano']))->assertRedirect(route('admin.quizzes.index', ['search' => 'Piano', 'manage' => $manage]));
        }
    }

    public function test_upload_replace_delete_and_media_validation_keep_files_in_quiz_directories()
    {
        $this->post(route('admin.quizzes.media.store', 'images'), ['file' => UploadedFile::fake()->image('note.png')])
            ->assertRedirect(route('admin.quizzes.index', ['manage' => 'images']));
        Storage::disk('public')->assertExists('quiz/images/note.png');
        $this->get(route('admin.quizzes.index', ['manage' => 'images']))->assertOk()->assertSee('note.png')->assertSee('Save replacement');
        $this->patch(route('admin.quizzes.media.update', ['path' => 'quiz/images/note.png']), ['file' => UploadedFile::fake()->image('new.png', 50, 50)])
            ->assertRedirect(route('admin.quizzes.index', ['manage' => 'images']));
        Storage::disk('public')->assertMissing('quiz/images/new.png');
        $this->withExceptionHandling()->post(route('admin.quizzes.media.store', 'images'), ['file' => UploadedFile::fake()->image('note.png')])
            ->assertSessionHasErrors('file', null, 'quizMediaUpload');
        $this->withExceptionHandling()->post(route('admin.quizzes.media.store', 'audio'), ['file' => UploadedFile::fake()->image('wrong.png')])
            ->assertSessionHasErrors('file', null, 'quizMediaUpload');
        Storage::disk('public')->put('private.txt', 'Keep');
        $this->withExceptionHandling()->delete(route('admin.quizzes.media.destroy', ['path' => 'private.txt']))->assertNotFound();
        $this->delete(route('admin.quizzes.media.destroy', ['path' => 'quiz/images/note.png']))->assertRedirect();
        Storage::disk('public')->assertMissing('quiz/images/note.png');
        Storage::disk('public')->assertExists('private.txt');
    }

    public function test_media_pagination_keeps_directory_state_and_replacement_errors_visible()
    {
        for ($i = 0; $i < 13; $i++) Storage::disk('public')->put('quiz/audio/'.sprintf('%02d', $i).'.mp3', 'sample');
        $response = $this->get(route('admin.quizzes.index', ['manage' => 'audio', 'media_page' => 2, 'search' => 'Piano']))->assertOk()->assertSee('12.mp3');
        $this->assertSame(1, $this->xpath($response)->query('//audio')->length);
        $path = 'quiz/audio/12.mp3';
        $this->withExceptionHandling()->patch(route('admin.quizzes.media.update', ['path' => $path, 'media_page' => 2]), ['file' => UploadedFile::fake()->image('wrong.png')])
            ->assertSessionHasErrors('file', null, 'quizMedia'.md5($path))
            ->assertRedirect(route('admin.quizzes.index', ['manage' => 'audio', 'media_page' => 2]));
    }

    public function test_selected_topic_filter_survives_editing_and_is_cleared_on_deletion()
    {
        $topic = create(Topic::class, ['name' => 'Theory', 'slug' => 'theory']);
        $quiz = $this->quiz();
        $quiz->topics()->attach($topic);
        $this->patch(route('admin.quizzes.topics.update', ['topic' => $topic, 'topic_id' => $topic->id]), ['name' => 'Music theory'])
            ->assertRedirect(route('admin.quizzes.index', ['topic_id' => $topic->id, 'manage' => 'topics']));
        $this->delete(route('admin.quizzes.topics.destroy', ['topic' => $topic->fresh(), 'topic_id' => $topic->id]))
            ->assertRedirect(route('admin.quizzes.index', ['manage' => 'topics']));
    }

    public function test_audio_uploads_use_the_same_forms_and_paths_are_bounded()
    {
        $pcm = str_repeat("\0", 200);
        $wav = 'RIFF'.pack('V', 36 + strlen($pcm)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 16000, 2, 16).'data'.pack('V', strlen($pcm)).$pcm;
        $upload = UploadedFile::fake()->createWithContent('interval.wav', $wav);
        $this->post(route('admin.quizzes.media.store', 'audio'), ['file' => $upload])->assertRedirect(route('admin.quizzes.index', ['manage' => 'audio']));
        Storage::disk('public')->assertExists('quiz/audio/interval.wav');
        foreach (['quiz/audio/../interval.wav', 'quiz/images/../../private.txt', 'quiz/audio\\interval.wav'] as $path) {
            $this->withExceptionHandling()->delete(route('admin.quizzes.media.destroy', ['path' => $path]))->assertNotFound();
        }
        auth('admin')->logout();
        $this->withExceptionHandling()->post(route('admin.quizzes.media.store', 'images'), ['file' => UploadedFile::fake()->image('blocked.png')])->assertRedirect();
        Storage::disk('public')->assertMissing('quiz/images/blocked.png');
    }

    public function test_batch_upload_validates_every_file_before_saving()
    {
        $this->post(route('admin.quizzes.media.store', 'images'), ['files' => [UploadedFile::fake()->image('a.png'), UploadedFile::fake()->image('b.png')]])->assertRedirect();
        Storage::disk('public')->assertExists(['quiz/images/a.png', 'quiz/images/b.png']);
        $this->withExceptionHandling()->post(route('admin.quizzes.media.store', 'images'), ['files' => [UploadedFile::fake()->image('c.png'), UploadedFile::fake()->image('a.png')]])
            ->assertSessionHasErrors('files', null, 'quizMediaUpload');
        Storage::disk('public')->assertMissing('quiz/images/c.png');
        $this->post(route('admin.quizzes.media.store', 'images'), ['files' => [UploadedFile::fake()->image('c.png'), UploadedFile::fake()->image('large.png')->size(5121)]])
            ->assertSessionHasErrors('files.1', null, 'quizMediaUpload');
        Storage::disk('public')->assertMissing('quiz/images/c.png');
    }
}
