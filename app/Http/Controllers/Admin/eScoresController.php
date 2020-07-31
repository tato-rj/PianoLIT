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
            'shelf_cover_path' => (new ImageUpload($request))->take('shelf_cover_image')
                                                       ->for(eScore::class)
                                                       ->name(str_slug($request->title).'-shelf')
                                                       ->upload(),
            'pdf_path' => $request->hasFile('pdf_file') ? 
            	$request->file('pdf_file')->storeAs('app/escores/pdf', str_slug($request->title).'.'.$request->file('pdf_file')->extension(), 'public') : null
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
            'shelf_cover_path' => (new ImageUpload($request))->take('shelf_cover_image')
                                                       ->for($escore)
                                                       ->name(str_slug($request->title).'-shelf')
                                                       ->upload(),
        ]);

        $file_fields = ['pdf_file'];

        foreach ($file_fields as $field) {
            $filetype = str_replace('_file', '', $field);
            $filename = str_replace('_file', '_path', $field);

            if ($request->hasFile($field)) {
                \Storage::disk('public')->delete($escore->$filename);
                
                $escore->$field = $request->file($field)->storeAs('app/escores/'.$filetype, str_slug($request->title).'.'.$request->file($field)->extension(), 'public');

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
