<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Files\Uploaders\ImageUpload;
use App\Shop\{eScore, eScoreTopic};
use App\Http\Controllers\Traits\ManageFiles;
use App\Services\Shop\ProductNames;
use App\Services\Admin\{DigitalProductsTable, DigitalTopics};
use App\Support\AdminTableFilters;
use Illuminate\Support\Facades\DB;

class eScoresController extends Controller
{
    use ManageFiles;

    public function index(Request $request, DigitalProductsTable $table)
    {
        if (request()->ajax())
            return eScore::datatable();

        return view('admin.pages.escores.index', $table->get($request, 'escores'));
    }

    public function topics(Request $request, DigitalTopics $topics)
    {
        return redirect($topics->url($request, 'escores'));
    }

    public function create()
    {
        $topics = eScoreTopic::all();

        return view('admin.pages.escores.create', compact('topics'));
    }

    public function store(Request $request)
    {
        $request->validate(['topics' => 'nullable|array', 'topics.*' => 'integer|distinct|exists:e_score_topics,id']);
        $request->merge(['title' => app(ProductNames::class)->validate($request->input('title'))]);

        $escore = eScore::create([
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

    public function topicStore(Request $request, DigitalTopics $topics)
    {
        return $topics->save($request, 'escores');
    }

    public function topicUpdate(Request $request, eScoreTopic $topic, DigitalTopics $topics)
    {
        return $topics->save($request, 'escores', $topic);
    }

    public function topicDestroy(Request $request, eScoreTopic $topic, DigitalTopics $topics)
    {
        return $topics->delete($request, 'escores', $topic);
    }

    public function edit(Request $request, eScore $escore, DigitalProductsTable $table)
    {
        $topics = eScoreTopic::ordered()->get();

        $returnFilters = AdminTableFilters::query($table->filters($request));

        return view('admin.pages.escores.edit', compact('escore', 'topics', 'returnFilters'));
    }

    public function update(Request $request, eScore $escore, DigitalProductsTable $table)
    {
        $returnFilters = AdminTableFilters::query($table->filters(Request::create('/', 'GET', $request->query())));
        app(\App\Services\Shop\DigitalEditor::class)->update($request, $escore, 'escores');

        return redirect()->route('admin.escores.edit', ['escore' => $escore] + $returnFilters)->with('status', 'Product details saved.');
    }

    public function updateStatus(Request $request, eScore $escore, DigitalProductsTable $table)
    {
        if ($request->query('return_to') !== 'directory') return $this->updateStatusFor($escore);
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $data = $request->validate(['published' => 'required|boolean']);
        DB::transaction(function () use ($escore, $data) {
            $product = $escore->newQuery()->whereKey($escore->id)->lockForUpdate()->firstOrFail();
            $product->update(['published_at' => $data['published'] ? ($product->published_at ?? now()) : null]);
        });
        return $table->redirect('escores', $filters)->with('status', $data['published'] ? 'Product published.' : 'Product unpublished.');
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

    public function destroy(Request $request, eScore $escore, DigitalProductsTable $table)
    {
        $filters = $request->query('return_to') === 'directory' ? $table->filters(Request::create('/', 'GET', $request->query())) : null;
        $escore->delete();
        if ($filters !== null) return $table->redirect('escores', $filters)->with('status', 'The eScore was deleted.');

        return redirect()->back()->with('status', 'The eScore has been successfuly deleted!');
    }
}
