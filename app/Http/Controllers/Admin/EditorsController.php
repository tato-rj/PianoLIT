<?php

namespace App\Http\Controllers\Admin;

use App\Admin;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class EditorsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('create', Admin::class);

        $editors = Admin::editors()->paginate(10);

        return view('admin.pages.editors.index', compact('editors'));
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
        $request->validate([
            'name' => 'required|string|min:4',
            'email' => 'required|email|unique:admins',
            'password' => 'string|confirmed|min:4'
        ]);

        Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'editor',
            'password' => \Hash::make($request->password)
        ]);

        return redirect()->back()->with('status', "{$request->name} has been successfully added as an editor!");
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Admin  $admin
     * @return \Illuminate\Http\Response
     */
    public function show(Admin $admin)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Admin  $editor
     * @return \Illuminate\Http\Response
     */
    public function edit(Admin $editor)
    {        
        return view('admin.pages.editors.edit', compact('editor'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Admin  $editor
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Admin $editor)
    {
        $this->authorize('update', $editor);

        if (isset($request->password)) {
            $request->validate(['password' => 'string|confirmed|min:4']);
            
            $editor->update(['password' => \Hash::make($request->password)]);
            
            $feedback = 'The password has been successfully updated!';
        } else {
            $request->validate([
                'name' => 'required|string|min:4',
                'email' => 'required|email',
            ]);

            $editor->update([
                'name' => $request->name,
                'email' => $request->email,
            ]);

            $feedback = "{$request->name}'s profile has been updated!";
        }

        return redirect()->back()->with('status', $feedback);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Admin  $editor
     * @return \Illuminate\Http\Response
     */
    public function destroy(Admin $editor)
    {
        $this->authorize('update', $editor);
        
        $editor->delete();

        return redirect()->back()->with('status', "$editor->name has been successfully deleted!");
    }
}
