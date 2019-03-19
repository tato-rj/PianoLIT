<?php

namespace App\Http\Controllers;

use App\Piece;
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
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'audio_path' => $request->hasFile('audio_path') ? $request->file('audio_path')->store('app/audio_path', 'public') : null,
            'audio_path_rh' => $request->hasFile('audio_path_rh') ? $request->file('audio_path_rh')->store('app/audio_path_rh', 'public') : null,
            'audio_path_lh' => $request->hasFile('audio_path_lh') ? $request->file('audio_path_lh')->store('app/audio_path_lh', 'public') : null,
            'score_path' => $request->hasFile('score_path') ? $request->file('score_path')->store('app/score_path', 'public') : null,
            'creator_id' => auth()->user()->id,
            'views' => mt_rand(5,15),
        ]);

        $piece->tags()->attach(array_merge($request->tags ?? [], $request->level ?? [], $request->length ?? [], $request->period ?? []));

        return redirect()->back()->with('success', "The piece has been successfully added!");
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
        //
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
            'composer_id' => $request->composer_id
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

        return redirect()->back()->with('success', "The piece has been successfully updated!");
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

        return redirect()->back()->with('success', "The piece has been successfully deleted!");
    }
}
