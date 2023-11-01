<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Files\Uploaders\ImageUpload;
use App\Shop\{eScore, eScoreTopic};
use App\Http\Controllers\Traits\ManageFiles;

class eScoresController extends Controller
{
    use ManageFiles;

    public function index()
    {
        return eScore::all();
        if (request()->ajax())
            return eScore::datatable();

        return view('admin.pages.escores.index');
    }

    public function topics()
    {
        $topics = eScoreTopic::ordered()->get();

        return view('admin.pages.escores.topics.index', compact('topics'));
    }

    public function create()
    {
        $topics = eScoreTopic::all();

        return view('admin.pages.escores.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $request->validate(['title' => 'unique:e_scores,title']);

        $escore = eScore::create([
            'slug' => str_slug($request->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $request->title,
            'author' => $request->author,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(eScore::class)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
            'mockup_path' => (new ImageUpload($request))->take('mockup_image')
                                                       ->for(eScore::class)
                                                       ->name(str_slug($request->title).'-mockup')
                                                       ->upload(),
            'pdf_path' => $this->hasFile('pdf_file')->upload('title', 'escores/pdf'),
            'audio_path' => $this->hasFile('audio_file')->upload('title', 'escores/audio')
        ]);
        
        $escore->topics()->attach($request->topics);

        return redirect(route('admin.escores.index'))->with('status', 'The eScore has been successfuly created!');
    }

    public function topicStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:e_score_topics|max:255',
        ]);

        eScoreTopic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "The topic has been successfully added!");
    }

    public function topicUpdate(Request $request, eScoreTopic $topic)
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

    public function topicDestroy(eScoreTopic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }

    public function edit(eScore $escore)
    {
        $topics = eScoreTopic::ordered()->get();

        return view('admin.pages.escores.edit', compact(['escore', 'topics']));
    }

    public function update(Request $request, eScore $escore)
    {
        $escore->update([
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'author' => $request->author,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($escore)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
            'mockup_path' => (new ImageUpload($request))->take('mockup_image')
                                                       ->for($escore)
                                                       ->name(str_slug($request->title).'-mockup')
                                                       ->upload(),
            'pdf_path' => $this->hasFile('pdf_file')->delete($escore->pdf_path)->upload('title', 'escores/pdf'),
            'audio_path' => $this->hasFile('audio_file')->delete($escore->audio_path)->upload('title', 'escores/audio')
        ]);

        $escore->topics()->sync($request->topics);

        return redirect(route('admin.escores.edit', $escore))->with('status', 'The eScore has been successfuly updated!');
    }

    public function updateStatus(eScore $escore)
    {
        return $this->updateStatusFor($escore);
    }

    public function uploadPreview(Request $request, eScore $escore)
    {
        $request->validate([
            'preview_image' => 'required|mimes:jpeg,png,jpg'
        ]);

        $escore->savePreview($request->file('preview_image'));

        return response(200);
    }
    
    public function removePreview(Request $request, eScore $escore)
    {
        $escore->deletePreview($request->preview_path);

        return back()->with('status', 'The preview image has been removed.');
    }

    public function destroy(eScore $escore)
    {
        $escore->delete();

        return redirect()->back()->with('status', 'The eScore has been successfuly deleted!');
    }
}
