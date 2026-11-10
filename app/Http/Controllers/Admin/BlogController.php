<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\PostForm;
use App\Files\Uploaders\ImageUpload;
use App\Blog\{Topic, Post};
use App\Services\Admin\PostsTable;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function create(Request $request, PostsTable $table)
    {
        $topics = Topic::orderBy('name')->get();
        $returnFilters = $table->state($request);

        return view('admin.pages.blog.post.create', compact('topics', 'returnFilters'));
    }

    public function store(Request $request, PostForm $form)
    {
        $returnFilters = app(PostsTable::class)->state($request);
        DB::transaction(function () use ($request, $form) {
            $post = Post::create([
                'slug' => str_slug($form->title),
                'creator_id' => auth()->guard('admin')->user()->id,
                'title' => $form->title,
                'description' => $form->description,
                'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                           ->for(Post::class)
                                                           ->name(str_slug($form->title))
                                                           ->withThumbnail()
                                                           ->cropped()
                                                           ->upload(),
                'content' => $form->content,
                'references' => $form->references ? serialize($form->references) : null,
                'cover_credits' => $form->cover_credits,
                'reading_time' => calculateReadingTime($form->content)
            ]);

            $post->topics()->attach($request->topics);
        });

        return redirect()->route('admin.posts.index', $returnFilters)->with('status', 'Post created.');
    }

    public function edit(Request $request, Post $post, PostsTable $table)
    {
        $topics = Topic::orderBy('name')->get();
        $returnFilters = $table->state($request);

        return view('admin.pages.blog.post.edit', compact('post', 'topics', 'returnFilters'));
    }

    public function update(Request $request, Post $post, PostForm $form)
    {
        $returnFilters = app(PostsTable::class)->state($request);
        DB::transaction(function () use ($request, $post, $form) {
            $post = Post::whereKey($post->id)->lockForUpdate()->firstOrFail();
            $post->update([
                'slug' => str_slug($form->title),
                'title' => $form->title,
                'description' => $form->description,
                'content' => $form->content,
                'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                           ->for($post)
                                                           ->name(str_slug($form->title))
                                                           ->withThumbnail()
                                                           ->cropped()
                                                           ->upload(),
                'references' => $form->references ? serialize($form->references) : null,
                'reading_time' => calculateReadingTime($form->content),
                'cover_credits' => $form->cover_credits
            ]);

            $post->topics()->sync($request->topics);
        });

        return redirect()->route('admin.posts.edit', ['post' => $post->fresh()] + $returnFilters)->with('status', 'Post saved.');
    }

    public function updateStatus(Request $request, Post $post, PostsTable $table)
    {
        if ($request->query('return_to') === 'directory') {
            $request->validate(['published' => 'required|boolean']);
            $filters = $table->filters(Request::create('/', 'GET', $request->query()));
            DB::transaction(function () use ($request, $post) {
                $post = Post::whereKey($post->id)->lockForUpdate()->firstOrFail();
                $post->update(['published_at' => $request->boolean('published') ? ($post->published_at ?: now()) : null]);
            });
            return $table->redirect($filters)->with('status', 'Publication saved.');
        }
        $request->validate(['attribute' => 'nullable|string|in:published_at']);
        return $this->updateStatusFor($post);
    }

    public function uploadImage(Request $request)
    {
        try {
            $path = \Storage::disk('public')->putFile(
                    "/blog/content_images", 
                    $request->file('file'));
        } catch (\Exception $e) {
            return response()->json('Sorry, something went wrong...', 404);
        }

        return response()->json(['location' => asset('storage/' . $path)]);
    }
    
    public function destroy(Request $request, Post $post, PostsTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $post->delete();

        return $table->redirect($filters)->with('status', 'Post deleted.');
    }
}
