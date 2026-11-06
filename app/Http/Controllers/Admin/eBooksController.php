<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Files\Uploaders\ImageUpload;
use App\Shop\{eBook, eBookTopic};
use App\Http\Controllers\Traits\ManageFiles;
use App\Services\Shop\ProductNames;
use App\Services\Admin\DigitalProductsTable;
use App\Support\AdminTableFilters;
use Illuminate\Support\Facades\DB;

class eBooksController extends Controller
{
    use ManageFiles;

    public function index(Request $request, DigitalProductsTable $table)
    {
        if (request()->ajax())
            return eBook::datatable();

        return view('admin.pages.ebooks.index', $table->get($request, 'ebooks'));
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
        $request->merge(['title' => app(ProductNames::class)->validate($request->input('title'))]);

        $ebook = eBook::create([
            'slug' => ProductNames::slug($request->title),
            'creator_id' => auth()->guard('admin')->user()->id,
            'title' => $request->title,
            'author' => $request->author,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'pages_count' => $request->pages_count,
            'price' => $request->price,
            'discount' => $request->discount,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(eBook::class)
                                                       ->name(str_slug($request->title))
                                                       ->upload(),
            'mockup_path' => (new ImageUpload($request))->take('mockup_image')
                                                       ->for(eBook::class)
                                                       ->name(str_slug($request->title).'-mockup')
                                                       ->upload(),
            'pdf_path' => $this->hasFile('pdf_file')->upload('title', 'ebooks/pdf'),
            'epub_path' => $this->hasFile('epub_file')->upload('title', 'ebooks/epub')
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

    public function edit(Request $request, eBook $ebook, DigitalProductsTable $table)
    {
        $topics = eBookTopic::ordered()->get();

        $returnFilters = AdminTableFilters::query($table->filters($request));

        return view('admin.pages.ebooks.edit', compact('ebook', 'topics', 'returnFilters'));
    }

    public function update(Request $request, eBook $ebook, DigitalProductsTable $table)
    {
        $returnFilters = AdminTableFilters::query($table->filters(Request::create('/', 'GET', $request->query())));
        app(\App\Services\Shop\DigitalEditor::class)->update($request, $ebook, 'ebooks');

        return redirect()->route('admin.ebooks.edit', ['ebook' => $ebook] + $returnFilters)->with('status', 'Product details saved.');
    }

    public function updateStatus(Request $request, eBook $ebook, DigitalProductsTable $table)
    {
        if ($request->query('return_to') !== 'directory') return $this->updateStatusFor($ebook);
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $data = $request->validate(['published' => 'required|boolean']);
        DB::transaction(function () use ($ebook, $data) {
            $product = $ebook->newQuery()->whereKey($ebook->id)->lockForUpdate()->firstOrFail();
            $product->update(['published_at' => $data['published'] ? ($product->published_at ?? now()) : null]);
        });
        return $table->redirect('ebooks', $filters)->with('status', $data['published'] ? 'Product published.' : 'Product unpublished.');
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

    public function destroy(Request $request, eBook $ebook, DigitalProductsTable $table)
    {
        $filters = $request->query('return_to') === 'directory' ? $table->filters(Request::create('/', 'GET', $request->query())) : null;
        $ebook->delete();
        if ($filters !== null) return $table->redirect('ebooks', $filters)->with('status', 'The eBook was deleted.');

        return redirect()->back()->with('status', 'The eBook has been successfuly deleted!');
    }
}
