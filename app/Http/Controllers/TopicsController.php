<?php

namespace App\Http\Controllers;

use App\Blog\Topic;
use Illuminate\Http\Request;

class TopicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('create', Topic::class);

        $topics = Topic::all();

        return view('admin.pages.blog.topics.index', compact('topics'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->authorize('create', Topic::class);

        $request->validate([
            'name' => 'required|unique:topics|max:255',
        ]);

        Topic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id,
            'type' => $request->type
        ]);

        return redirect()->back()->with('status', "The topic has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Blog\Topic  $topic
     * @return \Illuminate\Http\Response
     */
    public function show(Topic $topic)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Blog\Topic  $topic
     * @return \Illuminate\Http\Response
     */
    public function edit(Topic $topic)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Blog\Topic  $topic
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Topic $topic)
    {
        $this->authorize('create', Topic::class);

        $request->validate([
            'name' => 'required|max:255',
        ]);

        $topic->update([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'type' => $request->type,
        ]);

        return redirect()->back()->with('status', "The topic has been successfully updated!");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Blog\Topic  $topic
     * @return \Illuminate\Http\Response
     */
    public function destroy(Topic $topic)
    {
        $this->authorize('create', Topic::class);

        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }
}
