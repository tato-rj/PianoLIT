<?php

namespace App\Http\Controllers\WebApp;

use App\{Performance, Piece};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Cloudinary\CloudinaryApi;
use App\Events\Performances\PerformanceSubmitted;
use Illuminate\Support\Facades\Http;

class PerformancesController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Piece $piece)
    {
        $this->authorize('perform', $piece);

        $request->validate([
            'user-performance-video' => 'required|mimes:mp4,mov,avi,webm,wmv|max:100000'
        ]);

        $video = $request->file('user-performance-video');

        $response = Http::acceptJson()->attach(
            'video', file_get_contents($video), 'video.mp4'
        )->post(env('FILEMANAGER_URL'), [
            'secret' => env('FILEMANAGER_SECRET'),
            'piece_id' => $piece->id,
            'email' => auth()->user()->email,
            'user_id' => auth()->user()->id
        ]);

        $data = json_decode($response->body());

        if ($response->status() == 422)
            return back()->withErrors($data)->withInput();

        if (! $response->successful())
            return back()->with('error', 'Sorry, your video could not be proccessed at this time. If this problem persists, please send us a message at contact@pianolit.com');

        $performance = auth()->user()->performances()->create([
            'piece_id' => $piece->id,
            'display_name' => $request->display_name
        ]);

        event(new PerformanceSubmitted($performance));

        return back()->with('status', 'Your video is being processed, please wait a few moments.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Performance  $performance
     * @return \Illuminate\Http\Response
     */
    public function show(Performance $performance)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Performance  $performance
     * @return \Illuminate\Http\Response
     */
    public function edit(Performance $performance)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Performance  $performance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Performance $performance)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Performance  $performance
     * @return \Illuminate\Http\Response
     */
    public function destroy(Performance $performance)
    {
        $this->authorize('update', $performance);

        $response = (new CloudinaryApi)->delete($performance);

        if ($response['result'] == 'ok') {
            $performance->delete();

            return back()->with('status', 'The performance has been deleted.');
        }

        return back()->with('error', 'Your performance could not be deleted, please try again later.');
    }
}
