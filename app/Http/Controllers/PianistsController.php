<?php

namespace App\Http\Controllers;

use App\{Pianist, Country};
use App\Http\Requests\PianistForm;
use Illuminate\Http\Request;

class PianistsController extends Controller
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

        $countries = Country::all();
        $pianists = Pianist::orderBy($sort[0], $sort[1])->get();
        
        return view('admin.pages.pianists.index', compact(['pianists', 'countries']));
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
    public function store(Request $request, PianistForm $form)
    {
        $pianist = Pianist::create([
            'name' => $form->name,
            'biography' => $form->biography,
            'country_id' => $form->country_id,
            'itunes_id' => $form->itunes_id,
            'date_of_birth' => $form->date_of_birth,
            'date_of_death' => $form->date_of_death,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->back()->with('status', "$pianist->name has been successfully added!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function show(Pianist $pianist)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function edit(Pianist $pianist)
    {
        $countries = Country::all();
        
        return view('admin.pages.pianists.edit', compact(['pianist', 'countries']));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Pianist $pianist)
    {
        $this->authorize('update', $pianist);

        $pianist->update([
            'name' => $request->name,
            'date_of_birth' => carbon($request->date_of_birth)->format('Y-m-d'),
            'date_of_death' => $request->date_of_death ? carbon($request->date_of_death)->format('Y-m-d') : null,
            'itunes_id' => $request->itunes_id,
            'biography' => $request->biography,
            'country_id' => $request->country_id
        ]);
        
        return redirect()->back()->with('status', "$request->name has been updated");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function destroy(Pianist $pianist)
    {
        $this->authorize('update', $pianist);
    
        $pianist->delete();

        return redirect()->back()->with('status', "$pianist->name has been successfully deleted!");
    }
}
