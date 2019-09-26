<?php

namespace App\Http\Controllers;

use App\Timeline;
use Illuminate\Http\Request;

class TimelinesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $timelines = Timeline::orderBy('year')->get();
        $centuries = $timelines->groupBy('century');

        if (request()->has('century')) {
            $start = request('century') ?? $centuries->first()->first()->century;

            $end = $start + 99;

            $timelines = Timeline::whereBetween('year', [$start, $end])->orderBy('year')->get();
        }

        return view('admin.pages.timeline.index', compact(['timelines', 'centuries']));
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
        Timeline::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'year' => $request->year, 
            'event' => $request->event,
            'url' => $request->url,
            'type' => $request->type
        ]);

        return redirect()->back()->with('status', 'The event has been successfully added to the timeline!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Timeline  $timeline
     * @return \Illuminate\Http\Response
     */
    public function show(Timeline $timeline)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Timeline  $timeline
     * @return \Illuminate\Http\Response
     */
    public function edit(Timeline $timeline)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Timeline  $timeline
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Timeline $timeline)
    {
        $timeline->update([
            'year' => $request->year, 
            'type' => $request->type,
            'url' => $request->url,
            'event' => $request->event
        ]);

        return redirect()->back()->with('status', 'The event has been successfully edited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Timeline  $timeline
     * @return \Illuminate\Http\Response
     */
    public function destroy(Timeline $timeline)
    {
        $timeline->delete();

        return redirect()->back()->with('status', 'The event has been successfully removed from our database.');
    }
}
