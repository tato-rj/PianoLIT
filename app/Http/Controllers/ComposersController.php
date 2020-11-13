<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Composer, Piece};

class ComposersController extends Controller
{
    public function birthdays()
    {
        $calendar = Composer::all()->where('is_famous', true)->sortBy('month_of_birth')->groupBy('month_of_birth');

        return view('composers.birthdays.index', compact('calendar'));
    }
}
