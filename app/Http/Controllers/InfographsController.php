<?php

namespace App\Http\Controllers;

use App\Infograph;
use Illuminate\Http\Request;

class InfographsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
        $infograph = Infograph::create([
            'creator_id' => auth()->guard('admin')->user()->id,
            'name' => $request->name,
            'description' => $request->description,
            'orientation' => $request->orientation,
            'type' => $request->type
        ]);

        $infograph->uploadCoverImage($request);

        return redirect(route('admin.infographs.index'))->with('status', 'The infograph has been successfuly created!');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function show(Infograph $infograph)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function edit(Infograph $infograph)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Infograph $infograph)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Infograph  $infograph
     * @return \Illuminate\Http\Response
     */
    public function destroy(Infograph $infograph)
    {
        //
    }
}
