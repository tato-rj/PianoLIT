<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
use App\Http\Requests\PostForm;
use App\Services\Blog\PostViewCounts;
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
        $posts = Post::published()->latest()->orderByDesc('id')->filter($filters)->paginate(12)->withQueryString();
        $topics = Topic::orderBy('name')->get();
        $postViews = app(PostViewCounts::class)->publicTotals($posts->getCollection());

        return view('blog.index', compact('posts', 'topics', 'postViews'));
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
        $promotions = app(\App\Services\Blog\PostPromotions::class);
        $promotionCards = $promotions->cards($promotions->selectedFor($post));

        if (! $post->published_at) {
            if (auth()->guard('admin')->check())
                return view('blog.show', compact('post', 'suggestions', 'promotionCards'))->with('preview', true);

            abort(404);
        }

        $postViews = app(PostViewCounts::class)->publicTotals($suggestions->concat([$post]));
        return view('blog.show', compact('post', 'suggestions', 'promotionCards', 'postViews'));
    }

    public function gift(Post $post)
    {
        abort(410, 'Blog gifts are no longer available.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function app(Post $post)
    {
        return view('blog.app', compact('post'));
    }
}
