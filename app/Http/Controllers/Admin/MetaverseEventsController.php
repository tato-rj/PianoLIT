<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Metaverse\MetaverseEvent;
use Carbon\Carbon;

class MetaverseEventsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return MetaverseEvent::datatable();

        $events = MetaverseEvent::schedule()->get();

        return view('admin.pages.metaverse.events.index', compact('events'));
    }

    public function text()
    {
        return view('admin.pages.metaverse.events.text');
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

        MetaverseEvent::create([
            'location_id' => $request->location_id,
            'theme' => $request->theme,
            'duration' => $request->duration,
            'time' => $request->time,
            'date' => $date,
            'description' => $request->description,
        ]);

        return back()->with('status', "The event has been successfully created!");
    }

    public function update(Request $request, MetaverseEvent $metaverseEvent)
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

        $metaverseEvent->update([
            'location_id' => $request->location_id,
            'theme' => $request->theme,
            'duration' => $request->duration,
            'time' => $request->time,
            'date' => $date,
            'description' => $request->description,
        ]);

        return back()->with('status', "The event has been successfully updated!");
    }

    public function destroy(Request $request, MetaverseEvent $metaverseEvent)
    {
        $metaverseEvent->delete();

        return back()->with('status', "The event has been successfully deleted.");
    }
}
