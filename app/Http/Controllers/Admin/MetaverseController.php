<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Metaverse;
use Carbon\Carbon;

class MetaverseController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return Metaverse::datatable();

        $events = Metaverse::schedule()->get();

        return view('admin.pages.metaverse.events.index', compact('events'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'location_id' => 'required',
            'theme' => 'required',
            'duration' => 'required',
            'time' => 'required',
            'day' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);
        
        $date = Carbon::parse($request->day . ' ' . $request->month . ' ' . $request->year .' ' . $request->time);

        Metaverse::create([
            'location_id' => $request->location_id,
            'theme' => $request->theme,
            'duration' => $request->duration,
            'time' => $request->time,
            'date' => $date,
        ]);

        return back()->with('status', "The event has been successfully created!");
    }

    public function update(Request $request, Metaverse $metaverse)
    {
        $request->validate([
            'location_id' => 'required',
            'theme' => 'required',
            'duration' => 'required',
            'time' => 'required',
            'day' => 'required',
            'month' => 'required',
            'year' => 'required',
        ]);
        
        $date = Carbon::parse($request->day . ' ' . $request->month . ' ' . $request->year .' ' . $request->time);

        $metaverse->update([
            'location_id' => $request->location_id,
            'theme' => $request->theme,
            'duration' => $request->duration,
            'time' => $request->time,
            'date' => $date,
        ]);

        return back()->with('status', "The event has been successfully updated!");
    }

    public function destroy(Request $request, Metaverse $metaverse)
    {
        $metaverse->delete();

        return back()->with('status', "The event has been successfully deleted.");
    }
}
