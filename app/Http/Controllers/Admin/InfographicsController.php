<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Infograph\{Infograph, Topic};
use App\Http\Requests\InfographForm;

class InfographicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax())
            return Infograph::datatable();

        $topics = Topic::ordered()->get();

        return view('admin.pages.infographics.index', compact('topics'));
    }

    public function topics()
    {
        $topics = Topic::ordered()->get();

        return view('admin.pages.infographics.topics.index', compact('topics'));
    }

    public function store(Request $request, InfographForm $form)
    {
        $infograph = Infograph::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'name' => $form->name,
            'description' => $form->description,
            'width' => $form->width,
            'height' => $form->height,
            'slug' => str_slug($form->name),
            'published_at' => now()
        ]);

        $infograph->topics()->attach($request->topics);

        $infograph->uploadCoverImage($request, $crop = false);

        return redirect(route('admin.infographs.index'))->with('status', 'The infograph has been successfuly created!');
    }

    public function topicStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:infograph_topics|max:255',
        ]);

        Topic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "The topic has been successfully added!");
    }

    public function topicUpdate(Request $request, Topic $topic)
    {
        $request->validate([
            'name' => 'required|max:255',
        ]);
        
        $topic->update([
            'slug' => str_slug($request->name),
            'name' => $request->name
        ]);

        return redirect()->back()->with('status', "The topic has been successfully updated!");
    }

    public function topicDestroy(Topic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }

    public function edit(Infograph $infograph)
    {
        $topics = Topic::ordered()->get();

        return view('admin.pages.infographics.edit', compact(['infograph', 'topics']));
    }

    public function update(Request $request, Infograph $infograph, InfographForm $form)
    {
        $infograph->update([
            'slug' => str_slug($form->name),
            'name' => $form->name,
            'description' => $form->description,
            'width' => $form->width,
            'height' => $form->height
        ]);

        $infograph->topics()->sync($request->topics);

        $infograph->uploadCoverImage($request, $crop = false);

        return redirect()->back()->with('status', 'The infograph has been successfuly updated!');    
    }

    public function updateStatus(Request $request, Infograph $infograph)
    {
        $infograph->updateStatus($request->attribute);

        return response()->json(['status' => 'The infograph has been updated.']);
    }

    public function destroy(Infograph $infograph)
    {
        $infograph->delete();

        return redirect()->back()->with('status', 'The infograph has been successfuly deleted!');
    }
}
