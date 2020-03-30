<?php

namespace App\Http\Controllers;

use App\{Piece, Composer, Tag, Api};
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

    public function show(Piece $piece)
    {
        return view('pieces.show', compact('piece'));
    }

    /**
     * Display the collection for a specified resource.
     *
     * @param  \App\Piece  $piece
     * @return \Illuminate\Http\Response
     */
    public function collection(Piece $piece)
    {
        $pieces = $piece->siblings();
        
        $pieces->each(function($result) {
            (new Api)->setCustomAttributes($result, request('user_id'));
        });

        return $pieces;
    }

    public function similar(Piece $piece)
    {
        $pieces = $piece->similar();
        
        $pieces->each(function($result) {
            (new Api)->setCustomAttributes($result, request('user_id'));
        });
        
        return $pieces;
    }
}
