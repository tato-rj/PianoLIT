<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{Piece, Timeline};

class PiecesController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app')->except(['incrementViews']);
    }

    public function show(Request $request)
    {
        $piece = Piece::with(['composer', 'tags', 'favorites', 'tutorials'])->findOrFail($request->search);

        $piece->isFavorited($request->user_id);

        return [$piece];	
    }

    public function freepicks(Request $request)
    {
        return Piece::freePicks()->take(5)->get();
    }
    
    public function timeline($pieceId)
    {
        return Timeline::for($pieceId, 4);
    }

    public function collection(Request $request, $pieceId)
    {
        $pieces = Piece::findOrFail($pieceId)->siblings();
        
        $pieces->each->isFavorited($request->user_id);

        return $pieces;
    }

    public function similar(Request $request, $pieceId)
    {
        $pieces = Piece::findOrFail($pieceId)->similar();
        
        $pieces->each->isFavorited($request->user_id);
        
        return $pieces;
    }

    public function incrementViews(Request $request)
    {
    	$personalAccounts = [196, 244];

    	if (! in_array($request->user_id, $personalAccounts))
	        Piece::findOrFail($request->piece_id)->views()->create(['user_id' => $request->user_id]);
     
        return response()->json('The piece\'s views have been incremented', 200);
    }
}
