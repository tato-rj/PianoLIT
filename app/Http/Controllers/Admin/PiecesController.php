<?php

namespace App\Http\Controllers\Admin;

use App\{Piece, Composer, Tag, Api};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Files\Uploaders\ImageUpload;

class PiecesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax())
            return Piece::with(['tags', 'composer'])
                    ->filters(['creator_id', 'itunes', 'videos', 'score_path', 'audio_path', 'is_free'])
                    ->orderBy('updated_at', 'desc')
                    ->datatable();

        return view('admin.pages.pieces.index');
    }

    public function loadTags(Piece $piece)
    {
        $tags = Tag::byTypes($except = ['levels', 'periods', 'lengths']);

        return view('admin.pages.pieces.popups.tags', ['piece' => $piece, 'tagsByType' => $tags])->render();
    }

    public function loadLevels(Piece $piece)
    {
        $levels = Tag::levels()->get();

        return view('admin.pages.pieces.popups.levels', ['piece' => $piece, 'levels' => $levels])->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $composers = Composer::orderBy('name')->get();

        $types = Tag::byTypes($except = ['level', 'period', 'length']);

        return view('admin.pages.pieces.create', compact(['composers', 'types']));
    }

    public function alerts()
    {
        $data = auth()->user()->getAlert(request('alerts'));

        return view('admin.pages.pieces.alert', compact('data'))->render();
    }

    public function singleLookup(Request $request)
    {
        $field = $request->field;

        $results = Piece::selectRaw("$field, $field as output")
                        ->where($field, 'like', "%$request->input%")
                        ->get();

        return $results->unique('output')->values()->all();
    }
   
    public function multiLookup(Request $request)
    {
        $results = Piece::selectRaw('collection_name, catalogue_name, catalogue_number, composer_id, 
            CONCAT_WS(" ", collection_name, catalogue_name, catalogue_number) as output')
                        ->where('collection_name', 'like', "%$request->input%")
                        ->get();

        return $results->unique('output')->values()->all();
    }

    public function validateName(Request $request)
    {
        $request->validate(['name' => 'required']);
        $results = Piece::where('name', 'LIKE', "%$request->name%")
                        ->where('catalogue_number', 'like', $request->catalogue_number ?? '%')
                        ->where('collection_name', 'like', $request->collection_name ?? '%')->get();

        return view('admin.pages.pieces.validation', compact('results'))->render();        
    }

    public function descriptionAutoComplete(Request $request)
    {
        if ($request->has('piece_id')) {
            $similar = Piece::find($request->piece_id)->similar()->where('description', '!=', null);

            if (! $similar->isEmpty())
                return $similar->random()->description;
        }

        return Piece::whereNotNull('description')->inRandomOrder()->first()->description;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255',
            'key' => 'required',
            'composer_id' => 'required',
            'period' => 'required',
            'key' => 'required',
            'length' => 'required',
            'level' => 'required',
        ]);

        $piece = Piece::create([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'collection_name' => $request->collection_name,
            'collection_number' => $request->collection_number,
            'catalogue_name' => $request->catalogue_name,
            'catalogue_number' => $request->catalogue_number,
            'movement_number' => $request->movement_number,
            'curiosity' => $request->curiosity,
            'description' => $request->description,
            // 'videos' => $request->videos ? serialize($request->videos) : null,
            'itunes' => $request->itunes ? serialize($request->itunes) : null,
            'key' => $request->key,
            'score_url' => $request->is_public ? null : $request->score_url,
            'score_editor' => $request->is_public ? $request->score_editor : null,
            'score_publisher' => $request->is_public ? $request->score_publisher : null,
            'score_copyright' => $request->is_public ? 'Public Domain' : null,
            'composer_id' => $request->composer_id,
            'composed_in' => $request->composed_in,
            'published_in' => $request->published_in,
            'show_on_tour' => $request->show_on_tour ? 1 : 0,
            'audio_path' => $request->hasFile('audio') ? $request->file('audio')->store('app/audio', 'public') : null,
            'audio_path_rh' => $request->hasFile('audio_rh') ? $request->file('audio_rh')->store('app/audio_rh', 'public') : null,
            'audio_path_lh' => $request->hasFile('audio_lh') ? $request->file('audio_lh')->store('app/audio_lh', 'public') : null,
            'is_attributed_to' => $request->is_attributed_to ? 1 : 0,
            'creator_id' => auth()->user()->id,
        ]);

        if ($request->hasFile('score'))
            $piece->saveScore($request->file('score'));

        $piece->saveTutorials($request->videos);
        
        $piece->tags()->attach(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        return redirect()->back()->with('status', "The piece has been successfully added!");
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function edit(Piece $piece)
    {
        $composers = Composer::orderBy('name')->get();

        $types = Tag::byTypes($except = ['level', 'period', 'length']);

        return view('admin.pages.pieces.edit', compact(['composers', 'piece', 'types']));
    }

    public function testEscore(Piece $piece)
    {
        return (new \App\PDF\PDFGenerator)->pieces(collect([$piece]))
                                 ->request([])
                                 ->generate()
                                 ->stream();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Piece $piece)
    {
        $this->authorize('update', $piece);

        $request->validate([
            'name' => 'required|max:255',
            'key' => 'required',
            'composer_id' => 'required',
            'period' => 'required',
            'key' => 'required',
            'length' => 'required',
            'level' => 'required'
        ]);

        $piece->update([
            'name' => $request->name,
            'nickname' => $request->nickname,
            'collection_name' => $request->collection_name,
            'collection_number' => $request->collection_number,
            'catalogue_name' => $request->catalogue_name,
            'catalogue_number' => $request->catalogue_number,
            'movement_number' => $request->movement_number,
            'curiosity' => $request->curiosity,
            'description' => $request->description,
            // 'videos' => $request->videos ? serialize($request->videos) : null,
            'itunes' => $request->itunes ? serialize($request->itunes) : null,
            'key' => $request->key,
            'score_url' => $request->is_public ? null : $request->score_url,
            'score_editor' => $request->is_public ? $request->score_editor : null,
            'score_publisher' => $request->is_public ? $request->score_publisher : null,
            'score_copyright' => $request->is_public ? 'Public Domain' : null,
            'composer_id' => $request->composer_id,
            'composed_in' => $request->composed_in,
            'published_in' => $request->published_in,
            'show_on_tour' => $request->show_on_tour ? 1 : 0,
            'is_attributed_to' => $request->is_attributed_to ? 1 : 0,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($piece)
                                                       ->name(str_slug($piece->medium_name))
                                                       ->cropped()
                                                       ->upload(),
        ]);

        $piece->tags()->sync(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        $piece->saveTutorials($request->videos);

        $file_fields = ['audio_path', 'audio_path_rh', 'audio_path_lh'];

        foreach ($file_fields as $field) {
            $filename = str_replace('_path', '', $field);

            if ($request->hasFile($filename)) {
                \Storage::disk('public')->delete($piece->$field);
                
                $piece->$field = $request->file($filename)->store("app/{$filename}", 'public');

                $piece->save();
            }
        }

        if ($request->hasFile('score'))
            $piece->saveScore($request->file('score'));

        $piece->fresh()->searchable();
        
        return redirect()->back()->with('status', "The piece has been successfully updated!");
    }

    public function updateLevel(Request $request, Piece $piece)
    {
        $piece->tags()->detach($request->old_level_id);
        $piece->tags()->attach($request->new_level_id);

        $piece->searchable();

        return response()->json(['level_name' => ucfirst($piece->level->name), 'level_id' => $piece->level->id]);
    }

    public function updateTag(Request $request, Piece $piece)
    {
        $piece->tags()->toggle($request->id);

        $piece->searchable();
        
        return response()->json(['count' => $piece->fresh()->tags_count]);
    }

    public function highlight(Piece $piece)
    {
        Piece::free()->update(['is_free' => false]);
        
        $piece->update([
            'is_free' => true,
            'highlighted_at' => now()
        ]);

        return back()->with(['status' => 'The highlighted piece has been successfully updated!']);
    }

    public function hijack(Piece $piece)
    {        
        $piece->update(['created_at' => now()]);

        return back()->with(['status' => 'The piece has been successfully hijacked!']);
    }

    public function destroyFile(Request $request, Piece $piece)
    {
        $method = $request->method;

        if (method_exists($piece, $method)) {
            $piece->$method();

            return back()->with(['status' => 'The file has been successfully removed!']);
        }

        return back()->with(['status' => 'The file could not be removed']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function destroy(Piece $piece)
    {
        $this->authorize('update', $piece);
        
        $piece->delete();

        return redirect()->back()->with('status', "The piece has been successfully deleted!");
    }
}
