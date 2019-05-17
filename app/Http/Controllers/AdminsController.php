<?php

namespace App\Http\Controllers;

use App\{Admin, User, Piece, Tag, Composer, Subscription};
use App\Blog\Post;
use App\Tools\Stats;
use Illuminate\Http\Request;

class AdminsController extends Controller
{
    /**
     * Display the home page.
     *
     * @return \Illuminate\Http\Response
     */
    public function home()
    {
        $pieces_count = Piece::count();
        $tags_count = Tag::count();
        $composers_count = Composer::count();
        $users_count = User::count();

        $stats = new Stats;
        $pieces_graph = $stats->progress(15);
        $pieces_avg = $stats->average(7);
        $milestone = $stats->milestone($pieces_avg);
return $pieces_graph;
        return view('admin.pages.home.index', compact('pieces_count', 'tags_count', 'composers_count', 'users_count', 'pieces_graph', 'pieces_avg', 'milestone'));
    }

    /**
     * Display the blog page.
     *
     * @return \Illuminate\Http\Response
     */
    public function blog()
    {
        $posts = Post::latest()->get();

        return view('admin.pages.blog.index', compact('posts'));
    }

    /**
     * Display the subscriptions page.
     *
     * @return \Illuminate\Http\Response
     */
    public function subscriptions()
    {
        $subscriptions = Subscription::latest()->get();

        return view('admin.pages.subscriptions.index', compact('subscriptions'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $admin)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admin $admin)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $admin)
    {
        //
    }
}
