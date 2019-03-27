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
        $posts = Post::published()->paginate(12);

        return view('blog.index', compact('posts'));
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
            'cover_credits' => $form->cover_credits,
            'is_published' => false,
            'reading_time' => $form->reading_time
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
        $suggestions = Post::inRandomOrder()->take(4)->get();

        if (! $post->is_published) {
            if (auth()->guard('admin')->check())
                return view('blog.show', compact(['post', 'suggestions']))->with('preview', true);

            abort(404);
        }

        return view('blog.show', compact(['post', 'suggestions']));
    }

    public function search(Request $request)
    {
        $posts = Post::where('title', 'LIKE', '%'.$request->input.'%')
            ->orWhere('content', 'LIKE', '%'.$request->input.'%')->get();

        if ($request->wantsJson())
            return response()->json(['results' => $posts]);

        return view('components.search.results', compact('posts'))->render();
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
            'reading_time' => $form->reading_time,
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
