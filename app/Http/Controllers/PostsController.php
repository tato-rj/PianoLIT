<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
use App\Http\Requests\PostForm;
use Illuminate\Http\Request;
use App\Filters\BlogFilters;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(BlogFilters $filters)
    {
        $posts = Post::published()->latest()->filter($filters)->paginate(12);
        $topics = Topic::all();

        return view('blog.index', compact(['posts', 'topics']));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        $suggestions = $post->suggestions(8)->get();

        if (! $post->published_at) {
            if (auth()->guard('admin')->check())
                return view('blog.show', compact(['post', 'suggestions']))->with('preview', true);

            abort(404);
        }

        if (traffic()->isRealVisitor())
            $post->increment('views');

        return view('blog.show', compact(['post', 'suggestions']));
    }
}
