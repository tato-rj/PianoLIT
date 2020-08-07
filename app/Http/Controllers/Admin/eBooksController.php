<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Files\Uploaders\ImageUpload;
use App\Shop\{eBook, eBookTopic};

class eBooksController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return eBook::datatable();

        return view('admin.pages.ebooks.index');
    }

    public function topics()
    {
        $topics = eBookTopic::ordered()->get();

        return view('admin.pages.ebooks.topics.index', compact('topics'));
    }

    public function create()
    {
        $topics = eBookTopic::all();

        return view('admin.pages.ebooks.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $ebook = eBook::create([
            'slug' => str_slug($request->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(eBook::class)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
            'pdf_path' => $request->hasFile('pdf_file') ? 
            	$request->file('pdf_file')->storeAs('app/ebooks/pdf', 'pianolit-'.str_slug($request->title).'-'.lastnchar(mt_rand(), 4).'.'.$request->file('pdf_file')->extension(), 'public') : null,
            'epub_path' => $request->hasFile('epub_file') ? 
            	$request->file('epub_file')->storeAs('app/ebooks/epub', 'pianolit-'.str_slug($request->title).'-'.lastnchar(mt_rand(), 4).'.'.$request->file('epub_file')->extension(), 'public') : null,
        ]);
        
        $ebook->topics()->attach($request->topics);

        return redirect(route('admin.ebooks.index'))->with('status', 'The eBook has been successfuly created!');
    }

    public function topicStore(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:e_book_topics|max:255',
        ]);

        eBookTopic::create([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "The topic has been successfully added!");
    }

    public function topicUpdate(Request $request, eBookTopic $topic)
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

    public function topicDestroy(eBookTopic $topic)
    {
        $topic->delete();

        return redirect()->back()->with('status', "The topic has been successfully deleted!");
    }

    public function edit(eBook $ebook)
    {
        $topics = eBookTopic::ordered()->get();

        return view('admin.pages.ebooks.edit', compact(['ebook', 'topics']));
    }

    public function update(Request $request, eBook $ebook)
    {
        $ebook->update([
            'slug' => str_slug($request->title),
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($ebook)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
        ]);

        $file_fields = ['pdf_path' => 'pdf_file','epub_path' => 'epub_file'];

        foreach ($file_fields as $field => $file) {
            $filetype = str_replace('_path', '', $field);
            if ($request->hasFile($file)) {
                \Storage::disk('public')->delete($ebook->$field);
                $name = 'pianolit-'.str_slug($request->title) . '-' . lastnchar(mt_rand(), 4) . '.' . $request->file($file)->extension();
                $ebook->$field = $request->file($file)->storeAs('app/ebooks/'.$filetype, $name, 'public');

                $ebook->save();
            }
        }

        $ebook->topics()->sync($request->topics);

        return redirect()->back()->with('status', 'The eBook has been successfuly updated!');
    }

    public function updateStatus(eBook $ebook)
    {
        return $this->updateStatusFor($ebook);
    }

    public function uploadPreview(Request $request, eBook $ebook)
    {
        $request->validate([
            'preview_image' => 'required|mimes:jpeg,png,jpg'
        ]);

        $ebook->savePreview($request->file('preview_image'));

        return response(200);
    }
    
    public function removePreview(Request $request, eBook $ebook)
    {
        $ebook->deletePreview($request->preview_path);

        return back()->with('status', 'The preview image has been removed.');
    }

    public function destroy(eBook $ebook)
    {
        $ebook->delete();

        return redirect()->back()->with('status', 'The eBook has been successfuly deleted!');
    }
}
