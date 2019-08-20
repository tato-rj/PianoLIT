<?php

namespace Tests\Traits;

use App\Blog\Post;
use App\Quiz\Quiz;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait AdminEvents
{
    public function storeBlogPost()
    {
		Storage::fake('public');

        $post = make(Post::class, ['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

		$post->cropped_width = '200';
		$post->cropped_height = '200';
		$post->cropped_x = '0';
		$post->cropped_y = '0';

        $this->post(route('admin.posts.store'), $post->toArray());

        return Post::byTitle($post->title);
    }

    public function storeQuiz()
    {
        Storage::fake('public');

        $quiz = make(Quiz::class, ['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

        $quiz->cropped_width = '200';
        $quiz->cropped_height = '200';
        $quiz->cropped_x = '0';
        $quiz->cropped_y = '0';

        $this->post(route('admin.quizzes.store'), $quiz->toArray());

        return Quiz::byTitle($quiz->title);
    }
}
