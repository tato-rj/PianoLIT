<?php

namespace App\Http\Controllers;

use App\{Piece, Composer, Tag};
use Illuminate\Http\Request;

class PiecesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filtersArray = ['level', 'period', 'length'];
        $sortArray = ['asc', 'desc'];

        $request = array_keys(request()->toArray());

        if (request()->has('order') && in_array(request('order'), $sortArray))
            $sort = request('order');

        $pieces = new Piece;

        foreach ($request as $filter) {
            if ($filter == 'composer')
                $pieces = $pieces->where('composer_id', request($filter));

            if ($filter == 'creator_id')
                $pieces = $pieces->where('creator_id', request($filter));

            if (in_array($filter, $filtersArray)) {
                $pieces = $pieces->whereHas('tags', function($piece) use ($filter) {
                    $piece->where('name', request($filter));
                });
            }
        }

        $pieces = $pieces->orderBy('updated_at', $sort ?? 'desc')->get();

        return view('admin.pages.pieces.index', compact('pieces'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $composers = Composer::orderBy('name')->get();

        $types = Tag::byTypes($except = ['levels', 'periods', 'lengths']);

        return view('admin.pages.pieces.create', compact(['composers', 'types']));
    }

    public function singleLookup(Request $request)
    {
        $field = $request->field;

        $results = Piece::selectRaw("$field, $field as output")
                        ->where($field, 'like', "%$request->input%")
                        ->groupBy($field)
                        ->get();

        return $results;
    }
   
    public function multiLookup(Request $request)
    {
        $results = Piece::selectRaw('collection_name, catalogue_name, catalogue_number, composer_id, 
            CONCAT_WS(" ", collection_name, catalogue_name, catalogue_number) as output')
                        ->where('collection_name', 'like', "%$request->input%")
                        ->groupBy('collection_name', 'catalogue_name', 'catalogue_number', 'composer_id')
                        ->get();

        return $results;
    }

    public function validateName(Request $request)
    {
        $request->validate(['name' => 'required']);
        $results = Piece::where('name', 'LIKE', "%$request->name%")
                        ->where('catalogue_number', 'like', $request->catalogue_number ?? '%')
                        ->where('collection_name', 'like', $request->collection_name ?? '%')->get();

        return view('admin.pages.pieces.validation', compact('results'))->render();        
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
            'youtube' => $request->youtube ? serialize($request->youtube) : null,
            'itunes' => $request->itunes ? serialize($request->itunes) : null,
            'key' => $request->key,
            'score_url' => $request->score_url,
            'score_editor' => $request->score_editor,
            'score_publisher' => $request->score_publisher,
            'score_copyright' => $request->score_copyright,
            'composer_id' => $request->composer_id,
            'composed_in' => $request->composed_in,
            'audio_path' => $request->hasFile('audio_path') ? $request->file('audio_path')->store('app/audio_path', 'public') : null,
            'audio_path_rh' => $request->hasFile('audio_path_rh') ? $request->file('audio_path_rh')->store('app/audio_path_rh', 'public') : null,
            'audio_path_lh' => $request->hasFile('audio_path_lh') ? $request->file('audio_path_lh')->store('app/audio_path_lh', 'public') : null,
            'score_path' => $request->hasFile('score_path') ? $request->file('score_path')->store('app/score_path', 'public') : null,
            'creator_id' => auth()->user()->id,
            'views' => mt_rand(5,15),
        ]);

        $piece->tags()->attach(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        return redirect()->back()->with('status', "The piece has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function show(Piece $piece)
    {
        //
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

        $types = Tag::byTypes($except = ['levels', 'periods', 'lengths']);

        return view('admin.pages.pieces.edit', compact(['composers', 'piece', 'types']));
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
            'level' => 'required',
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
            'youtube' => $request->youtube ? serialize($request->youtube) : null,
            'itunes' => $request->itunes ? serialize($request->itunes) : null,
            'key' => $request->key,
            'score_url' => $request->score_url,
            'score_editor' => $request->score_editor,
            'score_publisher' => $request->score_publisher,
            'score_copyright' => $request->score_copyright,
            'composer_id' => $request->composer_id,
            'composed_in' => $request->composed_in,
        ]);

        $piece->tags()->sync(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        $file_types = ['audio_path', 'audio_path_rh', 'audio_path_lh', 'score_path'];

        foreach ($file_types as $type) {

            if ($request->hasFile($type)) {
                \Storage::disk('public')->delete($piece->$type);
                $piece->$type = $request->file($type)->store("app/{$type}", 'public');
            }

            $piece->save();
        }

        return redirect()->back()->with('status', "The piece has been successfully updated!");
    }

    public function incrementViews(Request $request)
    {
        Piece::find($request->piece_id)->increment('views');
     
        return response(200);
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
