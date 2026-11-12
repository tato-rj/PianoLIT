<?php

namespace App\Http\Controllers;

use App\Blog\{Post, Topic};
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
    public function index(Request $request, BlogFilters $filters)
    {
        $posts = Post::published()->latest()->orderByDesc('id')->filter($filters)->paginate(12)
            ->appends($request->only('topics'));
        $topics = Topic::orderBy('name')->get();
        $postViews = app(PostViewCounts::class)->publicTotals($posts->getCollection());
        $page = $posts->currentPage();
        $indexable = ! $request->query->has('topics') && $page <= $posts->lastPage();
        $canonical = $indexable ? route('posts.index', $page > 1 ? ['page' => $page] : []) : null;
        $response = response()->view('blog.index', compact('posts', 'topics', 'postViews', 'canonical'));

        return $indexable ? $response : $response->header('X-Robots-Tag', 'noindex');
    }

    public function sitemap()
    {
        $urls = Post::published()->without('topics')->orderBy('id')->pluck('slug')
            ->map(fn ($slug) => route('posts.show', $slug))->prepend(route('posts.index'));

        return response()->view('components.seo.sitemap', compact('urls'), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Blog\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        $preview = ! $post->published_at;
        abort_if($preview && ! auth()->guard('admin')->check(), 404);

        $suggestions = $this->related($post);
        $promotions = app(\App\Services\Blog\PostPromotions::class);
        $promotionCards = $promotions->cards($promotions->selectedFor($post));

        $postViews = $preview ? [] : app(PostViewCounts::class)->publicTotals($suggestions->concat([$post]));
        $response = response()->view('blog.show', compact('post', 'suggestions', 'promotionCards', 'postViews', 'preview'));

        return $preview ? $response->header('X-Robots-Tag', 'noindex') : $response;
    }

    private function related(Post $post)
    {
        $related = Post::published()->whereKeyNot($post->getKey());
        $topicIds = $post->topics->modelKeys();
        if ($topicIds) {
            $related->withCount(['topics as shared_topics_count' => fn ($query) => $query->whereIn('topics.id', $topicIds)])
                ->orderByDesc('shared_topics_count');
        }

        return $related->orderByDesc('published_at')->orderByDesc('id')->limit(8)->get();
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
        return response()->view('blog.app', compact('post'))->header('X-Robots-Tag', 'noindex');
    }
}
