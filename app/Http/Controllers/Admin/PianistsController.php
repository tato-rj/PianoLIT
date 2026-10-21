<?php

namespace App\Http\Controllers\Admin;

use App\{Pianist, Country};
use App\Http\Requests\PianistForm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Admin\PianistsTable;
use App\Support\AdminTableFilters;

class PianistsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, PianistsTable $table)
    {
        $countries = Country::orderBy('nationality')->get();
        return view('admin.pages.pianists.index', $table->get($request) + compact('countries'));
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
    public function store(PianistForm $form)
    {
        $pianist = Pianist::create([
            'slug' => str_slug($form->name),
            'name' => $form->name,
            'biography' => $form->biography,
            'cover_path' => $form->file('cover')->store('app/pianists', 'public'),
            'country_id' => $form->country_id,
            'itunes_id' => $form->itunes_id,
            'date_of_birth' => carbon($form->date_of_birth)->format('Y-m-d'),
            'date_of_death' => $form->date_of_death ? carbon($form->date_of_death)->format('Y-m-d') : null,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->route('admin.pianists.edit', $pianist)->with('status', "$pianist->name has been successfully added!");
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
    public function edit(Request $request, Pianist $pianist, PianistsTable $table)
    {
        $countries = Country::orderBy('nationality')->get();
        $returnFilters = AdminTableFilters::query($table->filters($request), 'name_asc');
        $pianist->load('creator');
        return view('admin.pages.pianists.edit', compact('pianist', 'countries', 'returnFilters'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function update(PianistForm $request, Pianist $pianist, PianistsTable $table)
    {
        $this->authorize('update', $pianist);
        $returnFilters = AdminTableFilters::query($table->filters(Request::create('/', 'GET', $request->query())), 'name_asc');

        $pianist->update([
            'slug' => str_slug($request->name),
            'name' => $request->name,
            'date_of_birth' => carbon($request->date_of_birth)->format('Y-m-d'),
            'date_of_death' => $request->date_of_death ? carbon($request->date_of_death)->format('Y-m-d') : null,
            'itunes_id' => $request->itunes_id,
            'biography' => $request->biography,
            'country_id' => $request->country_id
        ]);
    
        if ($request->hasFile('cover')) {
            \Storage::disk('public')->delete($pianist->cover_path);
            
            $pianist->update(['cover_path' => $request->file('cover')->store('app/pianists', 'public')]);
        }

        $redirect = redirect()->route('admin.pianists.edit', ['pianist' => $pianist] + $returnFilters)->with('status', "$request->name has been updated");
        return $request->filled('editor_section') ? $redirect->withFragment($request->editor_section) : $redirect;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Pianist  $pianist
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, Pianist $pianist, PianistsTable $table)
    {
        $this->authorize('update', $pianist);
        $filters = $table->filters(Request::create('/', 'GET', $request->query()));
    
        $pianist->delete();

        return $table->redirect($filters)->with('status', 'The pianist has been successfully deleted!');
    }
}
