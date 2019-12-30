<?php

namespace App\Http\Controllers;

use App\{Composer, Country};
use App\Http\Requests\ComposerForm;
use Illuminate\Http\Request;

class ComposersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $filters = ['name', 'date_of_birth', 'pieces_count'];
        
        $sort = ['name', 'asc'];

        if (request()->has('sort') && in_array(request('sort'), $filters))
            $sort[0] = request('sort');

        if (request()->has('order') && in_array(request('order'), ['asc', 'desc']))
            $sort[1] = request('order');

        $countries = Country::orderBy('nationality')->get();
        $composers = Composer::orderBy($sort[0], $sort[1])->get();

        return view('admin.pages.composers.index', compact(['composers', 'countries']));
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
            'cover_path' => $form->file('cover')->store('app/composers', 'public'),
            'gender' => $form->gender,
            'curiosity' => $form->curiosity,
            'country_id' => $form->country_id,
            'period' => $form->period,
            'date_of_birth' => $form->date_of_birth,
            'date_of_death' => $form->date_of_death,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "$composer->name has been successfully added!");
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
        $countries = Country::orderBy('nationality')->get();

        return view('admin.pages.composers.edit', compact(['composer', 'countries']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Composer $composer)
    {
        $this->authorize('update', $composer);

        $composer->update([
            'name' => $request->name,
            'date_of_birth' => $request->date_of_birth ? carbon($request->date_of_birth)->format('Y-m-d') : null,
            'date_of_death' => $request->date_of_death ? carbon($request->date_of_death)->format('Y-m-d') : null,
            'biography' => $request->biography,
            'gender' => $request->gender,
            'curiosity' => $request->curiosity,
            'country_id' => $request->country_id,
            'period' => strtolower($request->period)
        ]);

        if ($request->hasFile('cover')) {

            \Storage::disk('public')->delete($composer->cover_path);
            
            $composer->update(['cover_path' => $request->file('cover')->store('app/composers', 'public')]);
        }

        return redirect()->back()->with('status', "$request->name has been updated");
    }

    public function toggleFamous(Request $request, Composer $composer)
    {
        $composer->update(['is_famous' => ! $composer->is_famous]);

        return response()->json(['status' => 'The composer has been updated.']);
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

        return redirect()->back()->with('status', "$composer->name has been successfully deleted!");
    }
}
