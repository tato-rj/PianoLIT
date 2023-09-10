<?php

namespace App\Http\Controllers\WebApp;

use App\{Performance, Piece};
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\FileManager\FileManagerApi;
use App\Events\Performances\PerformanceSubmitted;

class PerformancesController extends Controller
{
    public function uploadUrl(Piece $piece)
    {
        $this->authorize('perform', $piece);

        return [
            'url' => env('FILEMANAGER_UPLOAD_URL'),
            'secret' => env('FILEMANAGER_SECRET'),
            'user' => auth()->user(),
            'piece' => $piece
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Piece $piece)
    {
        // $this->authorize('perform', $piece);

        // $request->validate([
        //     'user-performance-video' => 'required|mimes:mp4,mov,avi,webm,wmv|max:100000'
        // ]);

        // $response = (new FileManagerApi)->piece($piece)->upload($request->file('user-performance-video'))->getResponse();

        // $data = json_decode($response->body());

        // if ($response->status() == 422)
        //     return back()->withErrors($data)->withInput();

        // if (! $response->successful())
        //     return back()->with('error', $data->message);

        // $performance = auth()->user()->performances()->create([
        //     'piece_id' => $piece->id,
        //     'display_name' => $request->display_name
        // ]);

        // event(new PerformanceSubmitted($performance));

        // return back()->with('status', 'Your video is being processed, please wait a few moments.');
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

        $response = (new FileManagerApi)->delete($performance);

        if ($response->successful()) {
            $performance->delete();

            return back()->with('status', 'The performance has been deleted.');
        }

        return back()->with('error', 'Your performance could not be deleted, please try again later.');
    }
}
