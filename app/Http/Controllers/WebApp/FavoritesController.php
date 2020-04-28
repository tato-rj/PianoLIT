<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{User, Piece};

class FavoritesController extends Controller
{
	public function toggle(Piece $piece)
	{
		$status = auth()->user()->favorites()->toggle($piece);

		return response()->json(auth()->user()->favorites->contains($piece) ? 'Remove from favorites' : 'Add to favorites');
	}
}
