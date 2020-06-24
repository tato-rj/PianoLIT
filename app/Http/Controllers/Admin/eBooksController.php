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
            'shelf_cover_path' => (new ImageUpload($request))->take('shelf_cover_image')
                                                       ->for(eBook::class)
                                                       ->name(str_slug($request->title).'-shelf')
                                                       ->upload(),
            'pdf_path' => $request->hasFile('pdf_file') ? 
            	$request->file('pdf_file')->storeAs('app/ebooks/pdf', str_slug($request->title).'.'.$request->file('pdf_file')->extension(), 'public') : null,
            'epub_path' => $request->hasFile('epub_file') ? 
            	$request->file('epub_file')->storeAs('app/ebooks/epub', str_slug($request->title).'.'.$request->file('epub_file')->extension(), 'public') : null,
        ]);
        
        $ebook->topics()->attach($request->topics);

        return redirect(route('admin.ebooks.index'))->with('status', 'The eBook has been successfuly created!');
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
            'shelf_cover_path' => (new ImageUpload($request))->take('shelf_cover_image')
                                                       ->for($ebook)
                                                       ->name(str_slug($request->title).'-shelf')
                                                       ->upload(),
        ]);

        $file_fields = ['pdf_file', 'epub_file'];

        foreach ($file_fields as $field) {
            $filetype = str_replace('_file', '', $field);
            $filename = str_replace('_file', '_path', $field);

            if ($request->hasFile($field)) {
                \Storage::disk('public')->delete($ebook->$filename);
                
                $ebook->$field = $request->file($field)->storeAs('app/ebooks/'.$filetype, str_slug($request->title).'.'.$request->file($field)->extension(), 'public');

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
            'preview_image' => 'required|mimes:jpeg,png'
        ]);

        $ebook->savePreview($request->file('preview_image'));

        return response(200);
    }
    
    public function removePreview(Request $request, eBook $ebook)
    {
        $ebook->deletePreview($request->preview_path);

        return response(200);
    }

    public function destroy(eBook $ebook)
    {
        $ebook->delete();

        return redirect()->back()->with('status', 'The eBook has been successfuly deleted!');
    }
}
