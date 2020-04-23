<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{User, Piece};

class FavoritesController extends Controller
{
	public function toggle(Piece $piece)
	{
		return auth()->user()->favorites()->toggle($piece);
	}
}
