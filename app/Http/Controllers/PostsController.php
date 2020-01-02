<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
use App\Http\Requests\PostForm;
use Illuminate\Http\Request;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::published()->latest()->paginate(12);

        return view('blog.index', compact('posts'));
    }

    public function topic(Topic $topic)
    {
        $topics = Topic::exclude([$topic->id])->get();
        $posts = Post::published()->latest()->byTopic($topic)->paginate(12);

        return view('blog.topic', compact(['posts', 'topics', 'topic']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $topics = Topic::all();

        return view('admin.pages.blog.post.create', compact('topics'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, PostForm $form)
    {
        $post = Post::create([
            'slug' => str_slug($form->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $form->title,
            'description' => $form->description,
            'content' => $form->content,
            'references' => $request->references ? serialize($request->references) : null,
            'gift_path' => $form->gift_path,
            'cover_credits' => $form->cover_credits,
            'reading_time' => calculateReadingTime($form->content)
        ]);

        $post->topics()->attach($request->topics);

        $post->uploadCoverImage($request);

        return redirect(route('admin.posts.index'))->with('status', 'The post has been successfuly created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        $suggestions = Post::exclude([$post->id])->suggestions(4)->get();

        if (! $post->published_at) {
            if (auth()->guard('admin')->check())
                return view('blog.show', compact(['post', 'suggestions']))->with('preview', true);

            abort(404);
        }

        if (traffic()->isRealVisitor())
            $post->increment('views');

        return view('blog.show', compact(['post', 'suggestions']));
    }

    public function search(Request $request)
    {
        $posts = Post::published()->search(['title', 'content'], $request->input)->get();

        if ($request->wantsJson())
            return response()->json(['results' => $posts]);

        return view('components.overlays.search.results', compact('posts'))->render();
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $topics = Topic::all();

        return view('admin.pages.blog.post.edit', compact(['post', 'topics']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post, PostForm $form)
    {
        $post->update([
            'slug' => str_slug($form->title),
            'title' => $form->title,
            'description' => $form->description,
            'content' => $form->content,
            'references' => $request->references ? serialize($request->references) : null,
            'gift_path' => $form->gift_path,
            'reading_time' => calculateReadingTime($form->content),
            'cover_credits' => $form->cover_credits
        ]);

        $post->topics()->sync($request->topics);

        $post->uploadCoverImage($request);

        return redirect()->back()->with('status', 'The post has been successfuly updated!');
    }

    public function updateStatus(Request $request, Post $post)
    {
        $post->updateStatus();

        return response()->json(['status' => 'The post has been ' . $post->status . '.']);
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->back()->with('status', 'The post has been successfuly deleted!');
    }
}
