<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Composer, Piece};

class ComposersController extends Controller
{
    public function birthdays(Request $request)
    {
        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        $calendar = $request->option != 'all' ? Composer::famous()->get() : Composer::all();

        $calendar = $calendar->sortBy('month_of_birth')->sortBy('day_of_birth')->groupBy('month_of_birth');

		if ($request->wantsJson())
			return view('composers.birthdays.calendar', compact(['calendar', 'months']))->render();

        return view('composers.birthdays.index', compact(['calendar', 'months']));
    }
}
