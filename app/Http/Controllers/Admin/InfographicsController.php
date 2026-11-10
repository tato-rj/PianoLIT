<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Infograph\{Infograph, Topic};
use App\Files\Uploaders\ImageUpload;
use App\Http\Requests\InfographForm;
use App\Services\Admin\{InfographicsTable, InfographicTopics};
use App\Support\AdminTableFilters;
use Illuminate\Support\Facades\DB;

class InfographicsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, InfographicsTable $table)
    {
        if (request()->ajax())
            return Infograph::datatable();

        return view('admin.pages.infographics.index', $table->get($request));
    }

    public function topics(Request $request, InfographicTopics $topics)
    {
        return redirect($topics->url($request));
    }

    public function store(Request $request, InfographForm $form)
    {
        $filters = app(InfographicsTable::class)->filters(Request::create('/', 'GET', $request->query()));
        [$width, $height] = getimagesize($form->file('cover_image')->getRealPath());
        $attributes = [
            'creator_id' => auth()->guard('admin')->user()->id,
            'name' => $form->name,
            'description' => $form->description,
            'width' => $width,
            'height' => $height,
            'slug' => str_slug($form->name),
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(Infograph::class)
                                                       ->name(str_slug($form->name))
                                                       ->withThumbnail()
                                                       ->upload()
        ];
        DB::transaction(function () use ($attributes, $form) {
            $infograph = Infograph::create($attributes);
            $infograph->topics()->attach($form->input('topics', []));
        });

        return redirect()->route('admin.infographs.index', AdminTableFilters::query($filters))->with('status', 'Infographic created.');
    }

    public function topicStore(Request $request, InfographicTopics $topics)
    {
        return $topics->save($request);
    }

    public function topicUpdate(Request $request, Topic $topic, InfographicTopics $topics)
    {
        return $topics->save($request, $topic);
    }

    public function topicDestroy(Request $request, Topic $topic, InfographicTopics $topics)
    {
        return $topics->delete($request, $topic);
    }

    public function edit(Request $request, Infograph $infograph, InfographicsTable $table)
    {
        $topics = Topic::ordered()->get();

        $returnFilters = AdminTableFilters::query($table->filters($request));
        return view('admin.pages.infographics.edit', compact('infograph', 'topics', 'returnFilters'));
    }

    public function update(Request $request, Infograph $infograph, InfographForm $form)
    {
        $filters = app(InfographicsTable::class)->filters(Request::create('/', 'GET', $request->query()));
        [$width, $height] = $form->hasFile('cover_image') ? getimagesize($form->file('cover_image')->getRealPath()) : [$infograph->width, $infograph->height];
        $attributes = [
            'slug' => str_slug($form->name),
            'name' => $form->name,
            'description' => $form->description,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($infograph)
                                                       ->name(str_slug($form->name))
                                                       ->withThumbnail()
                                                       ->upload(),
            'width' => $width,
            'height' => $height
        ];
        DB::transaction(function () use ($infograph, $attributes, $form) {
            Infograph::whereKey($infograph->id)->lockForUpdate()->firstOrFail();
            $infograph->update($attributes);
            $infograph->topics()->sync($form->input('topics', []));
        });

        return redirect()->route('admin.infographs.edit', ['infograph' => $infograph] + AdminTableFilters::query($filters))->with('status', 'Infographic saved.');
    }

    public function updateStatus(Request $request, Infograph $infograph, InfographicsTable $table)
    {
        $request->validate(['attribute' => 'nullable|string|in:published_at,giftable_at']);
        if ($request->query('return_to') !== 'directory') return $this->updateStatusFor($infograph);
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        $data = $request->validate(['attribute' => 'required|string|in:published_at,giftable_at', 'enabled' => 'required|boolean']);
        DB::transaction(function () use ($infograph, $data) {
            $record = Infograph::whereKey($infograph->id)->lockForUpdate()->firstOrFail();
            $record->update([$data['attribute'] => $data['enabled'] ? ($record->{$data['attribute']} ?? now()) : null]);
        });
        return $table->redirect($filters)->with('status', 'Infographic status saved.');
    }

    public function destroy(Request $request, Infograph $infograph, InfographicsTable $table)
    {
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
        DB::transaction(function () use ($infograph) {
            Infograph::whereKey($infograph->id)->lockForUpdate()->firstOrFail()->delete();
        });

        return $table->redirect($filters)->with('status', 'Infographic deleted.');
    }
}
