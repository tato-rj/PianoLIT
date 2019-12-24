<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Piece;

class HighlightsController extends Controller
{
    public function update(Piece $piece)
    {
    	Piece::free()->update(['is_free' => false]);
    	
    	$piece->update(['is_free' => true]);

    	return back()->with(['status' => 'The highlighted piece has been successfully updated!']);
    }
}
