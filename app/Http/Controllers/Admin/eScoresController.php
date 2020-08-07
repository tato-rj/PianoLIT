<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Files\Uploaders\ImageUpload;
use App\Shop\{eScore, eScoreTopic};

class eScoresController extends Controller
{
    public function index()
    {
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
        $escore = eScore::create([
            'slug' => str_slug($request->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(eScore::class)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
            'pdf_path' => $request->hasFile('pdf_file') ? 
            	$request->file('pdf_file')->storeAs('app/escores/pdf', 'pianolit-'.str_slug($request->title).'-'.lastnchar(mt_rand(), 4).'.'.$request->file('pdf_file')->extension(), 'public') : null
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
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($escore)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
        ]);

        $file_fields = ['pdf_path' => 'pdf_file'];

        foreach ($file_fields as $field => $file) {
            $filetype = str_replace('_path', '', $field);
            if ($request->hasFile($file)) {
                \Storage::disk('public')->delete($escore->$field);
                $name = 'pianolit-'.str_slug($request->title) . '-' . lastnchar(mt_rand(), 4) . '.' . $request->file($file)->extension();
                $escore->$field = $request->file($file)->storeAs('app/escores/'.$filetype, $name, 'public');

                $escore->save();
            }
        }

        $escore->topics()->sync($request->topics);

        return redirect()->back()->with('status', 'The eScore has been successfuly updated!');
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
