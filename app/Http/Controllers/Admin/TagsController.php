<?php

namespace App\Http\Controllers\Admin;

use App\Tag;
use App\Services\Admin\TagsTable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\{Rule, ValidationException};

class TagsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, TagsTable $table)
    {
        $this->authorize('create', Tag::class);

        $data = $table->get($request);
        $data['editingTag'] = Tag::with('creator:id,name')->find($request->session()->get('editing_tag_id'));
        return view('admin.pages.tags.index', $data);
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
    public function store(Request $request, TagsTable $table)
    {
        $this->authorize('create', Tag::class);

        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $data = $request->validateWithBag('tagForm', [
            'name' => 'required|string|unique:tags|max:255',
            'type' => ['required', 'string', Rule::in(array_keys($table->types()))],
            'ordering' => 'nullable|integer|min:1|max:65535',
        ]);

        Tag::create($data + ['creator_id' => auth()->guard('admin')->id()]);

        return $table->redirect($filters)->with('status', "The tag has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function edit(Tag $tag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Tag $tag, TagsTable $table)
    {
        $this->authorize('create', Tag::class);

        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        try {
            $data = $request->validateWithBag('tagForm', [
                'name' => 'required|string|max:255',
                'type' => ['sometimes', 'required', 'string', Rule::in(array_keys($table->types()))],
                'ordering' => 'nullable|integer|min:1|max:65535',
            ]);
        } catch (ValidationException $exception) {
            if (!$request->expectsJson()) $request->session()->flash('editing_tag_id', $tag->id);
            throw $exception;
        }

        $tag->update($data);

        return $table->redirect($filters)->with('status', "The tag has been successfully updated!");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Tag  $tag
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Tag $tag, TagsTable $table)
    {
        $this->authorize('create', Tag::class);

        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        DB::transaction(function () use ($tag) { $tag->delete(); });

        return $table->redirect($filters)->with('status', "The tag has been successfully deleted!");
    }
}
