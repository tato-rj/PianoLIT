<?php

namespace Tests\Traits;

use App\Blog\Post;
use App\Quiz\Quiz;
use App\Shop\eBook;
use App\Infograph\Infograph;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

trait AdminEvents
{
    public function storeEBook()
    {
        Storage::fake('public');
        
        $ebook = make(eBook::class, [
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
            'shelf_cover_image' => UploadedFile::fake()->image('shelf_cover.jpg'),
            'pdf_file' => UploadedFile::fake()->image('file.pdf'),
            'epub_file' => UploadedFile::fake()->image('file.epub')
        ]);

        $this->post(route('admin.ebooks.store'), $ebook->toArray());

        return eBook::byTitle($ebook->title);
    }

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

        $this->post(route('admin.quizzes.store'), [
            'title' => $quiz->title,
            'level_id' => $quiz->level_id,
            'description' => $quiz->description,
            'questions' => $quiz->questions(),
            'topics' => $quiz->topics,
            'cover_image' => $quiz->cover_image,
            'cropped_width' => '200',
            'cropped_height' => '200',
            'cropped_x' => '0',
            'cropped_y' => '0',
        ]);

        return Quiz::byTitle($quiz->title);
    }

    public function storeInfograph()
    {
        Storage::fake('public');

        $infograph = make(Infograph::class, ['cover_image' => UploadedFile::fake()->image('cover.jpg')]);

        $this->post(route('admin.infographs.store'), [
            'name' => $infograph->name,
            'description' => $infograph->description,
            'tags' => $infograph->tags,
            'cover_image' => $infograph->cover_image,
            'width' => 100,
            'height' => 100
        ]);

        return Infograph::where('name', $infograph->name)->first();
    }
}
