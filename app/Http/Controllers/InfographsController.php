<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Merchandise\Purchase;
use App\Infograph\{Infograph, Topic};
use Illuminate\Http\Request;
use App\Http\Requests\InfographForm;
use App\Notifications\{InfographDownload, InfographVoted};

class InfographsController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:2')->only('updateScore');
    }

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

        return view('admin.pages.infographs.index', compact('topics'));
    }

    public function topics()
    {
        $topics = Topic::ordered()->get();

        return view('admin.pages.infographs.topics.index', compact('topics'));
    }

    public function show(Infograph $infograph)
    {
        $related = $infograph->related();

        return view('resources.infographs.show', compact(['infograph', 'related']));
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
    public function store(Request $request, InfographForm $form)
    {
        $infograph = Infograph::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'name' => $form->name,
            'description' => $form->description,
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

    /**
     * Display the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->increment('downloads');

            auth()->user()->purchase($infograph);
            
            Admin::notifyAll(new InfographDownload($infograph));
        }

        $file = request('size') == 'lg' ? $infograph->cover_path : $infograph->thumbnail_path;

        return \Storage::disk('public')->download($file);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function edit(Infograph $infograph)
    {
        $topics = Topic::ordered()->get();

        return view('admin.pages.infographs.edit', compact(['infograph', 'topics']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Infograph $infograph, InfographForm $form)
    {
        $infograph->update([
            'slug' => str_slug($form->name),
            'name' => $form->name,
            'description' => $form->description
        ]);

        $infograph->topics()->sync($request->topics);

        $infograph->uploadCoverImage($request, $crop = false);

        return redirect()->back()->with('status', 'The infograph has been successfuly updated!');    
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

    public function updateStatus(Request $request, Infograph $infograph)
    {
        $infograph->updateStatus($request->attribute);

        return response()->json(['status' => 'The infograph has been updated.']);
    }

    public function updateScore(Request $request, Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->updateScore($request->liked);
            Admin::notifyAll(new InfographVoted($infograph, $request->liked));
        }

        return response(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function destroy(Infograph $infograph)
    {
        $infograph->delete();

        return redirect()->back()->with('status', 'The infograph has been successfuly deleted!');
    }

    public function topicDestroy(Topic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }
}
