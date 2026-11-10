<?php

namespace App\Http\Controllers\Admin;

use App\Blog\Topic;
use App\Services\Admin\{BlogTopics, PostsTable};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TopicsController extends Controller
{
    public function index(Request $request, PostsTable $table)
    {
        $this->authorize('create', Topic::class);
        return redirect($table->modalUrl($request, 'topics'));
    }

    public function store(Request $request, BlogTopics $topics)
    {
        $this->authorize('create', Topic::class);
        return $topics->save($request);
    }

    public function create(Request $request, PostsTable $table)
    {
        return $this->index($request, $table);
    }

    public function show(Request $request, Topic $topic, PostsTable $table)
    {
        return $this->index($request, $table);
    }

    public function edit(Request $request, Topic $topic, PostsTable $table)
    {
        return $this->index($request, $table);
    }

    public function update(Request $request, Topic $topic, BlogTopics $topics)
    {
        $this->authorize('create', Topic::class);
        return $topics->save($request, $topic);
    }

    public function destroy(Request $request, Topic $topic, BlogTopics $topics)
    {
        $this->authorize('create', Topic::class);
        return $topics->delete($request, $topic);
    }
}
