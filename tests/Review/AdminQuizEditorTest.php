<?php

namespace Tests\Review;

use App\Admin;
use App\Quiz\{Quiz, Level, Topic};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Redis, Storage};

class AdminQuizEditorTest extends ReviewTestCase
{
    private $level;

    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Storage::fake('public');
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->level = create(Level::class, ['name' => 'Beginner', 'slug' => 'beginner']);
    }

    private function quiz(array $attributes = [])
    {
        return Model::withoutEvents(fn () => create(Quiz::class, $attributes + [
            'title' => 'Reading notes', 'slug' => 'reading-notes', 'creator_id' => auth('admin')->id(),
            'level_id' => $this->level->id, 'description' => 'Answer questions about notes.',
            'cover_path' => 'quiz/cover_image/cover.jpg',
            'questions' => serialize([['Q' => 'Name this note [/storage/quiz/images/note.png]', 'A' => ['C[x]', 'D']]]),
        ]));
    }

    private function data(array $attributes = []): array
    {
        return $attributes + ['quiz_editor' => '1', 'title' => 'Saved quiz', 'description' => 'Answer these questions.',
            'level_id' => $this->level->id, 'questions' => [0 => ['Which note?', 'C', 'D', '', '']], 'correct_answers' => [0 => '2']];
    }

    private function xpath($response): \DOMXPath
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return new \DOMXPath($dom);
    }

    public function test_create_and_edit_share_sections_fields_and_saved_correct_answer_selection()
    {
        foreach ([route('admin.quizzes.create'), route('admin.quizzes.edit', $this->quiz())] as $url) {
            $response = $this->get($url.'?search=notes')->assertOk()->assertSee('Quiz details')->assertSee('Cover image')->assertSee('Topics')->assertSee('Questions')
                ->assertSee('js/views/admin-quiz-editor.js')->assertDontSee('cdnjs.cloudflare.com/ajax/libs/cropperjs');
            $xpath = $this->xpath($response);
            $this->assertSame(1, $xpath->query('//form[@id="quiz-editor"]')->length);
            $this->assertSame(4, $xpath->query('//section[contains(@class,"admin-editor-section")]')->length);
            $this->assertSame(1, $xpath->query('//form[@id="quiz-editor"]//input[@name="questions[0][0]"]')->length);
            $this->assertSame(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[contains(@href,"search=notes")]')->length);
        }
        $xpath = $this->xpath($response);
        $this->assertSame(1, $xpath->query('//input[@name="correct_answers[0]" and @value="1" and @checked]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="questions[0][1]" and @value="C"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="questions[0][0]" and @value="Name this note"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="question_media[0]" and @value="/storage/quiz/images/note.png"]')->length);
    }

    public function test_sparse_question_indices_and_correct_choices_save_in_existing_public_format()
    {
        $data = $this->data(['questions' => [0 => ['First?', 'A', 'B', '', ''], 3 => ['Second? [/storage/quiz/audio/interval.mp3]', 'Major', 'Minor', '', '']], 'correct_answers' => [0 => '2', 3 => '1']]);
        $this->post(route('admin.quizzes.store', ['search' => 'quiz']), $data)->assertRedirect(route('admin.quizzes.index', ['search' => 'quiz']));
        $quiz = Quiz::where('title', 'Saved quiz')->firstOrFail();
        $this->assertSame([['Q' => 'First?', 'A' => ['A', 'B[x]']], ['Q' => 'Second? [/storage/quiz/audio/interval.mp3]', 'A' => ['Major[x]', 'Minor']]], $quiz->questions);
        $this->assertSame(1, $quiz->getAnswer(0));
        $this->assertSame(0, $quiz->getAnswer(1));
    }

    public function test_update_without_new_cover_retains_files_and_clears_unchecked_topics()
    {
        $quiz = $this->quiz();
        Storage::disk('public')->put($quiz->cover_path, 'cover');
        Storage::disk('public')->put($quiz->thumbnail_path, 'thumbnail');
        $topic = create(Topic::class);
        $quiz->topics()->attach($topic);
        $this->patch(route('admin.quizzes.update', ['quiz' => $quiz, 'search' => 'notes']), $this->data())
            ->assertRedirect(route('admin.quizzes.edit', ['quiz' => $quiz, 'search' => 'notes']));
        $this->assertSame('reading-notes', $quiz->fresh()->slug);
        $this->assertSame('Saved quiz', $quiz->fresh()->title);
        $this->assertSame('quiz/cover_image/cover.jpg', $quiz->fresh()->cover_path);
        $this->assertSame(0, $quiz->topics()->count());
        Storage::disk('public')->assertExists([$quiz->cover_path, $quiz->thumbnail_path]);
    }

    public function test_validation_restores_edited_questions_level_correct_choice_and_empty_topics()
    {
        $quiz = $this->quiz();
        $topic = create(Topic::class);
        $quiz->topics()->attach($topic);
        $otherLevel = create(Level::class, ['name' => 'Intermediate', 'slug' => 'intermediate']);
        $data = $this->data(['title' => 'x', 'level_id' => $otherLevel->id, 'questions' => [5 => ['Changed question?', 'E', 'F', '', '']], 'correct_answers' => [5 => '2']]);
        $this->withExceptionHandling()->from(route('admin.quizzes.edit', $quiz))->patch(route('admin.quizzes.update', $quiz), $data)->assertSessionHasErrors('title');
        $response = $this->get(route('admin.quizzes.edit', $quiz))->assertOk();
        $xpath = $this->xpath($response);
        $this->assertSame(1, $xpath->query('//input[@name="questions[5][0]" and @value="Changed question?"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="correct_answers[5]" and @value="2" and @checked]')->length);
        $this->assertSame(1, $xpath->query('//select[@name="level_id"]/option[@selected and @value="'.$otherLevel->id.'"]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="topics[]" and @checked]')->length);
        $this->assertSame('Reading notes', $quiz->fresh()->title);
    }

    public function test_invalid_correct_choice_and_short_answer_lists_do_not_save()
    {
        $this->withExceptionHandling()->post(route('admin.quizzes.store'), $this->data(['questions' => [0 => ['Which?', 'C', '', '', '']], 'correct_answers' => [0 => '2']]))
            ->assertSessionHasErrors(['questions.0', 'correct_answers.0']);
        $response = $this->get(route('admin.quizzes.create'))->assertOk()->assertSee('Select a correct answer with text.')->assertSee('Enter at least two answers.');
        $this->assertDatabaseMissing('quizzes', ['title' => 'Saved quiz']);
        $this->post(route('admin.quizzes.store'), $this->data(['level_id' => 99999, 'topics' => [99999]]))->assertSessionHasErrors(['level_id', 'topics.0']);
    }

    public function test_existing_marker_based_admin_requests_keep_their_format()
    {
        $data = $this->data(['questions' => [0 => ['Which?', 'C[x]', 'D']]]);
        unset($data['quiz_editor'], $data['correct_answers']);
        $this->post(route('admin.quizzes.store'), $data)->assertRedirect();
        $this->assertSame([['Q' => 'Which?', 'A' => ['C[x]', 'D']]], Quiz::where('title', 'Saved quiz')->firstOrFail()->questions);
    }

    public function test_cover_replacement_keeps_the_existing_crop_and_thumbnail_workflow()
    {
        $quiz = $this->quiz();
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['cover_image' => UploadedFile::fake()->image('cover.jpg', 120, 80), 'cropped_width' => 100, 'cropped_height' => 56, 'cropped_x' => 10, 'cropped_y' => 10]))->assertRedirect();
        $quiz = $quiz->fresh();
        $this->assertNotSame('quiz/cover_image/cover.jpg', $quiz->cover_path);
        Storage::disk('public')->assertExists([$quiz->cover_path, $quiz->thumbnail_path]);
        $dimensions = getimagesize(Storage::disk('public')->path($quiz->cover_path));
        $this->assertSame([100, 56], array_slice($dimensions, 0, 2));
    }

    public function test_quiz_and_topic_updates_roll_back_together()
    {
        $quiz = $this->quiz();
        $old = create(Topic::class);
        $new = create(Topic::class);
        $quiz->topics()->attach($old);
        DB::unprepared("CREATE TRIGGER reject_quiz_topics BEFORE INSERT ON quiz_quiz_topic BEGIN SELECT RAISE(ABORT, 'Fixture failure'); END;");
        try {
            $this->patch(route('admin.quizzes.update', $quiz), $this->data(['topics' => [$new->id]]));
            $this->fail('Expected the fixture pivot failure.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('Fixture failure', $exception->getMessage());
        }
        $this->assertSame('Reading notes', $quiz->fresh()->title);
        $this->assertSame([$old->id], $quiz->topics()->pluck('quiz_topics.id')->all());
    }

    public function test_media_picker_is_scoped_searchable_paginated_and_read_only()
    {
        for ($i = 0; $i < 13; $i++) Storage::disk('public')->put('quiz/images/'.sprintf('%02d', $i).'.png', 'image');
        Storage::disk('public')->put('quiz/audio/other.wav', 'audio');
        $response = $this->get(route('admin.quizzes.media.choose', 'images'))->assertOk()->assertHeader('X-Quiz-Media-Library', '1');
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertSame(12, $this->xpath($response)->query('//button[@data-select-quiz-media]')->length);
        $response->assertDontSee('other.wav');
        $response = $this->get(route('admin.quizzes.media.choose', ['type' => 'images', 'page' => 2]))->assertOk();
        $this->assertSame(1, $this->xpath($response)->query('//button[@data-select-quiz-media]')->length);
        Storage::disk('public')->put('quiz/images/100% note.png', 'image');
        $response = $this->get(route('admin.quizzes.media.choose', ['type' => 'images', 'search' => '%']))->assertOk()->assertSee('100% note.png');
        $this->assertSame(1, $this->xpath($response)->query('//button[@data-select-quiz-media]')->length);
        $this->get(route('admin.quizzes.media.choose', ['type' => 'images', 'search' => 'missing']))->assertOk()->assertSee('No files match this search.');
        $this->assertSame(0, Quiz::count());
        $this->withExceptionHandling()->get(route('admin.quizzes.media.choose', 'other'))->assertNotFound();
        $this->getJson(route('admin.quizzes.media.choose', ['type' => 'images', 'page' => 'bad']))->assertStatus(422);
        auth('admin')->logout();
        $this->get(route('admin.quizzes.media.choose', 'images'))->assertRedirect();
    }

    public function test_chosen_image_audio_and_removal_keep_the_existing_saved_question_format()
    {
        $quiz = $this->quiz();
        Storage::disk('public')->put('quiz/images/note [sharp] #1.png', 'image');
        Storage::disk('public')->put('quiz/audio/interval.wav', 'audio');
        $image = '/storage/quiz/images/note%20%5Bsharp%5D%20%231.png';
        $response = $this->get(route('admin.quizzes.media.choose', 'images'))->assertOk();
        $this->assertSame(1, $this->xpath($response)->query('//button[@data-path="'.$image.'"]')->length);
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => $image]]))->assertRedirect();
        $quiz = $quiz->fresh();
        $this->assertSame('Which note? ['.$image.']', $quiz->questions[0]['Q']);
        $this->assertSame($image, $quiz->questions()[0]['image']);
        $this->assertSame(1, $quiz->getAnswer(0));
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => '/storage/quiz/audio/interval.wav']]))->assertRedirect();
        $quiz = $quiz->fresh();
        $this->assertSame('/storage/quiz/audio/interval.wav', $quiz->questions()[0]['audio']);
        $this->assertNull($quiz->questions()[0]['image']);
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => '']]))->assertRedirect();
        $this->assertSame('Which note?', $quiz->fresh()->questions[0]['Q']);
    }

    public function test_media_validation_restores_selection_and_rejects_foreign_or_escaped_paths()
    {
        $quiz = $this->quiz();
        Storage::disk('public')->put('quiz/images/note.png', 'image');
        $this->withExceptionHandling()->from(route('admin.quizzes.edit', $quiz))->patch(route('admin.quizzes.update', $quiz), $this->data(['title' => 'x', 'question_media' => [0 => '/storage/quiz/images/note.png']]))
            ->assertSessionHasErrors('title');
        $response = $this->get(route('admin.quizzes.edit', $quiz))->assertOk();
        $xpath = $this->xpath($response);
        $this->assertSame(1, $xpath->query('//input[@name="questions[0][0]" and @value="Which note?"]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="question_media[0]" and @value="/storage/quiz/images/note.png"]')->length);
        foreach (['https://other.example/storage/quiz/images/note.png', '/storage/quiz/images/%2e%2e/private.png', '/storage/private.png', '/storage/quiz/images/missing.png'] as $value) {
            $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => $value]]))->assertSessionHasErrors('question_media.0');
        }
        Storage::disk('public')->put('private.png', 'private');
        symlink(Storage::disk('public')->path('private.png'), Storage::disk('public')->path('quiz/images/escape.png'));
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => '/storage/quiz/images/escape.png']]))->assertSessionHasErrors('question_media.0');
        unlink(Storage::disk('public')->path('quiz/images/escape.png'));
        $this->get(route('admin.quizzes.media.choose', 'images'))->assertOk()->assertDontSee('escape.png');
    }

    public function test_missing_saved_media_can_be_retained_or_explicitly_removed()
    {
        $quiz = $this->quiz();
        $this->patch(route('admin.quizzes.update', $quiz), $this->data(['question_media' => [0 => '/storage/quiz/images/note.png']]))->assertRedirect();
        $this->assertSame('Which note? [/storage/quiz/images/note.png]', $quiz->fresh()->questions[0]['Q']);
        $response = $this->get(route('admin.quizzes.edit', $quiz->fresh()))->assertOk()->assertSee('File unavailable.');
        $this->assertSame(0, $this->xpath($response)->query('//img[@data-question-media-image and @src]')->length);
        $this->patch(route('admin.quizzes.update', $quiz->fresh()), $this->data(['question_media' => [0 => '']]))->assertRedirect();
        $this->assertSame('Which note?', $quiz->fresh()->questions[0]['Q']);
    }
}
