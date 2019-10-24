<?php

namespace App\Http\Controllers;

use App\{Infograph, Admin};
use Illuminate\Http\Request;
use App\Http\Requests\InfographForm;
use App\Notifications\{InfographDownload, InfographVoted};

class InfographsController extends Controller
{
    public function __construct()
    {
        $this->middleware('throttle:5')->only('download');
        $this->middleware('throttle:2')->only('updateScore');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $infographs = Infograph::all();
        $types = Infograph::types();

        return view('admin.pages.infographs.index', compact(['infographs', 'types']));
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
    public function store(Request $request, InfographForm $form)
    {
        $infograph = Infograph::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'name' => $form->name,
            'description' => $form->description,
            'slug' => str_slug($form->name),
            'type' => $form->type
        ]);

        $infograph->uploadCoverImage($request, $crop = false);

        return redirect(route('admin.infographs.index'))->with('status', 'The infograph has been successfuly created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function download(Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->increment('downloads');
            Admin::notifyAll(new InfographDownload($infograph));
        }

        return response()->download(storage_path('app/public/' . $infograph->cover_path));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function edit(Infograph $infograph)
    {
        $types = Infograph::types();

        return view('admin.pages.infographs.edit', compact(['infograph', 'types']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Infograph $infograph, InfographForm $form)
    {
        $infograph->update([
            'slug' => str_slug($form->name),
            'name' => $form->name,
            'description' => $form->description,
            'type' => $form->type
        ]);

        $infograph->uploadCoverImage($request, $crop = false);

        return redirect()->back()->with('status', 'The infograph has been successfuly updated!');    
    }

    public function updateStatus(Request $request, Infograph $infograph)
    {
        $infograph->updateStatus();

        return response()->json(['status' => 'The infograph has been ' . $infograph->status . '.']);
    }

    public function updateScore(Request $request, Infograph $infograph)
    {
        if (traffic()->isRealVisitor()) {
            $infograph->updateScore($request->liked);
            Admin::notifyAll(new InfographVoted($infograph, $request->liked));
        }

        return response(200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function destroy(Infograph $infograph)
    {
        $infograph->delete();

        return redirect()->back()->with('status', 'The infograph has been successfuly deleted!');
    }
}
