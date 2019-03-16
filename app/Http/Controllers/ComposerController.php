<?php

namespace App\Http\Controllers;

use App\Composer;
use App\Http\Requests\ComposerForm;
use Illuminate\Http\Request;

class ComposerController extends Controller
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
    public function store(Request $request, ComposerForm $form)
    {
        $composer = Composer::create([
            'name' => $form->name,
            'biography' => $form->biography,
            'curiosity' => $form->curiosity,
            'country_id' => $form->country_id,
            'period' => $form->period,
            'date_of_birth' => $form->date_of_birth,
            'date_of_death' => $form->date_of_death,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('success', "$composer->name has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function show(Composer $composer)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function edit(Composer $composer)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Composer $composer, ComposerForm $form)
    {
        $this->authorize('update', $composer);

        $composer->update([
            'name' => $request->name,
            'date_of_birth' => $form->date_of_birth,
            'date_of_death' => $form->date_of_death,
            'biography' => $request->biography,
            'curiosity' => $request->curiosity,
            'country_id' => $request->country_id,
            'period' => $form->period
        ]);
        
        return redirect()->back()->with('success', "$request->name has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Composer $composer)
    {
        $this->authorize('update', $composer);

        if ($composer->pieces()->exists())
            $composer->pieces->each->delete();
    
        $composer->delete();

        return redirect()->back()->with('success', "$composer->name has been successfully deleted!");
    }
}
