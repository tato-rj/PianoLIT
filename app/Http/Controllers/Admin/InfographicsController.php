<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Infograph\{Infograph, Topic};
use App\Files\Uploaders\ImageUpload;
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
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(Infograph::class)
                                                       ->name(str_slug($form->name))
                                                       ->withThumbnail()
                                                       ->upload()
        ]);

        $infograph->topics()->attach($request->topics);

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
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($infograph)
                                                       ->name(str_slug($form->name))
                                                       ->withThumbnail()
                                                       ->upload(),
            'width' => $form->width,
            'height' => $form->height
        ]);

        $infograph->topics()->sync($request->topics);

        return redirect(route('admin.infographs.edit', $infograph))->with('status', 'The infograph has been successfuly updated!');    
    }

    public function updateStatus(Infograph $infograph)
    {
        return $this->updateStatusFor($infograph);
    }

    public function destroy(Infograph $infograph)
    {
        $infograph->delete();

        return redirect()->back()->with('status', 'The infograph has been successfuly deleted!');
    }
}
