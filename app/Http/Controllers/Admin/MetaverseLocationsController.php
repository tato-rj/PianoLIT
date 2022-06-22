<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Metaverse\MetaverseLocation;

class MetaverseLocationsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return MetaverseLocation::datatable();

        $locations = MetaverseLocation::all();

        return view('admin.pages.metaverse.locations.index', compact('locations'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'url' => 'required',
            'venue' => 'required',
        ]);

        MetaverseLocation::create([
            'name' => $request->name,
            'url' => $request->url,
            'venue' => $request->venue,
            'capacity' => $request->capacity,
        ]);

        return back()->with('status', "The location has been successfully created!");
    }

    public function update(Request $request, MetaverseLocation $metaverseLocation)
    {
        $request->validate([
            'name' => 'required',
            'url' => 'required',
            'venue' => 'required',
        ]);
    
        $metaverseLocation->update([
            'name' => $request->name,
            'url' => $request->url,
            'venue' => $request->venue,
            'capacity' => $request->capacity,
        ]);

        return back()->with('status', "The location has been successfully updated!");
    }

    public function destroy(Request $request, MetaverseLocation $metaverseLocation)
    {
        $metaverseLocation->delete();

        return back()->with('status', "The location has been successfully deleted.");
    }
}
