<?php

namespace App\Http\Controllers\Admin;

use App\{Composer, Country, Tag};
use App\Http\Requests\ComposerForm;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Files\Uploaders\ImageUpload;
use App\Services\OpenAI\ComposerBiography;
use App\Services\OpenAI\BiographyGenerationException;
use App\Services\Admin\ComposersTable;
use App\Support\AdminTableFilters;

class ComposersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ComposersTable $table)
    {
        if (request()->ajax())
            return Composer::datatable();

        $countries = Country::orderBy('nationality')->get();
        $moods = Tag::mood()->mostPopular()->pluck('name')->take(25)->sortBy('name');

        return view('admin.pages.composers.index', $table->get($request) + compact('countries', 'moods'));
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
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for(Composer::class)
                                                       ->name(str_slug($form->name))
                                                       ->upload(),
            'gender' => $form->gender,
            'ethnicity' => $form->ethnicity,
            'curiosity' => $form->curiosity,
            'country_id' => $form->country_id,
            'period' => $form->period,
            'is_famous' => $form->is_famous ? 1 : 0,
            'is_pedagogical' => $form->is_pedagogical ? 1 : 0,
            'date_of_birth' => $form->date_of_birth,
            'date_of_death' => $form->date_of_death,
            'mood' => $form->mood,
            'creator_id' => auth()->guard('admin')->user()->id
        ]);

        return redirect()->route('admin.composers.edit', $composer)->with('status', "$composer->name has been successfully added!");
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
    public function edit(Composer $composer, Request $request, ComposersTable $table)
    {
        $request->validate(['pieces_page' => 'nullable|integer|min:1|max:1000000']);
        $countries = Country::orderBy('nationality')->get();
        $moods = Tag::mood()->mostPopular()->pluck('name')->take(25)->sortBy('name');

        $returnFilters = AdminTableFilters::query($table->filters($request), 'name_asc');
        $composer->load('creator');
        $pieces = $composer->pieces()->setEagerLoads([])->select('pieces.*')->with('tags')
            ->orderBy('name')->orderBy('id')->paginate(\App\Support\AdminPagination::PER_PAGE, ['*'], 'pieces_page')
            ->appends($returnFilters)->fragment('composer-repertoire');

        return view('admin.pages.composers.edit', compact('composer', 'countries', 'moods', 'returnFilters', 'pieces'));
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
            'ethnicity' => $request->ethnicity,
            'curiosity' => $request->curiosity,
            'is_famous' => $request->is_famous ? 1 : 0,
            'is_pedagogical' => $request->is_pedagogical ? 1 : 0,
            'country_id' => $request->country_id,
            'period' => strtolower($request->period),
            'mood' => $request->mood,
            'cover_path' => (new ImageUpload($request))->take('cover_image')
                                                       ->for($composer)
                                                       ->name(str_slug($request->name))
                                                       ->upload(),
        ]);

        return redirect()->back()->with('status', "$request->name has been updated");
    }

    public function regenerateBiography(Request $request, Composer $composer, ComposerBiography $generator)
    {
        $this->authorize('update', $composer);

        $data = $request->validate(['biography' => 'required|string|max:20000']);

        try {
            return response()->json(['biography' => $generator->generate($composer->name, $data['biography'])]);
        } catch (BiographyGenerationException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->getCode());
        }
    }

    public function toggleFamous(Request $request, Composer $composer, ComposersTable $table)
    {
        $filters = $table->filters($request);
        $request->validate(['is_famous' => 'sometimes|boolean', 'return_to' => 'sometimes|in:directory']);
        $composer->update(['is_famous' => $request->has('is_famous') ? $request->boolean('is_famous') : ! $composer->is_famous]);

        if ($request->input('return_to') === 'directory' && !$request->expectsJson()) {
            return $table->redirect($filters)->with('status', 'The composer has been updated.');
        }
        return response()->json(['status' => 'The composer has been updated.']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Composer  $composer
     * @return \Illuminate\Http\Response
     */
    public function destroy(Composer $composer, Request $request, ComposersTable $table)
    {
        $this->authorize('update', $composer);
        $filters = $table->filters($request);

        if ($composer->pieces()->exists())
            $composer->pieces->each->delete();
    
        $composer->delete();

        return $table->redirect($filters)->with('status', "$composer->name has been successfully deleted!");
    }
}
