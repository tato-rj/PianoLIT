<?php

namespace Tests\Feature;

use Tests\AppTest;
use App\Quiz\Quiz;
use Tests\Traits\AdminEvents;
use Illuminate\Support\Facades\Storage;

class QuizTest extends AppTest
{
    use AdminEvents;

    /** @test */
    public function an_admin_can_create_a_new_quiz()
    {
        $this->signIn();
        
        $quiz = $this->storeQuiz();

        $this->assertDatabaseHas('quizzes', ['title' => $quiz->title]);

        Storage::disk('public')->assertExists($quiz->cover_path);
    }

    /** @test */
    public function a_thumbnail_is_automatically_generated_when_a_cover_image_is_saved()
    {
        $this->signIn();

        $quiz = $this->storeQuiz();

        $this->assertNotEquals($quiz->thumbnail_path, $quiz->cover_path);
        
        $this->assertTrue(Storage::disk('public')->exists($quiz->cover_path));

        $this->assertTrue(Storage::disk('public')->exists($quiz->thumbnail_path));
    }

    /** @test */
    public function an_admin_can_publish_a_quiz()
    {
        $this->signIn();

        $quiz = $this->storeQuiz();
         
        $this->assertNull($quiz->published_at);

        $this->patch(route('admin.quizzes.update-status', $quiz->slug));

        $this->assertNotNull($quiz->fresh()->published_at);
    }

    /** @test */
    public function an_admin_can_update_a_quiz()
    {
        $this->signIn();

        $quiz = $this->storeQuiz();

        $title = $quiz->title;
        
        $update = make(Quiz::class, ['title' => 'New title']);
        
        $this->patch(route('admin.quizzes.update', $quiz->slug), [
            'title' => $update->title,
            'level_id' => $update->level_id,
            'description' => $update->description,
            'questions' => $update->questions
        ]);

        $this->assertNotEquals($title, $quiz->fresh()->title);       
    }

    /** @test */
    public function an_admin_can_remove_a_quiz()
    {
        $this->signIn();

        $quiz = $this->storeQuiz();

        $this->delete(route('admin.quizzes.destroy', $quiz->slug));

        $this->assertDatabaseMissing('quizzes', ['title' => $quiz->title]);

        Storage::disk('public')->assertMissing($quiz->cover_path);
    }

    /** @test */
    public function a_quiz_automatically_increments_its_view_each_time_it_is_viewed()
    {
        $quiz = create(Quiz::class, ['published_at' => now()]);

        $views = $quiz->views;

        $this->get(route('quizzes.show', $quiz->slug));

        $this->assertNotEquals($views, $quiz->fresh()->views);
    }
}
