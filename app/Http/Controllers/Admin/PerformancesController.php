<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Performance;
use App\Events\Performances\PerformanceApproved;
use App\FileManager\FileManagerApi;

class PerformancesController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return Performance::with(['piece', 'user'])
                    ->orderBy('updated_at', 'desc')
                    ->datatable();

        return view('admin.pages.performances.index');
    }

    public function update(Request $request, Performance $performance)
    {
        $performance->update([
            'video_url' => $request->video_url,
            'thumbnail_url' => $request->thumbnail_url
        ]);

        return back()->with('status', 'The performance has been updated.');
    }

    public function approve(Performance $performance)
    {
        if ($performance->isApproved()) {
            $performance->disapprove();
        } else {
            $performance->approve();

            event(new PerformanceApproved($performance));
        }

        return view('components.alert', [
            'color' => 'green',
            'message' => '<i class="fas fa-check-circle mr-2"></i>The performance status has been updated',
            'temporary' => true,
            'dismissible' => true,
            'floating' => 'top'
        ])->render();
    }

    public function destroy(Performance $performance)
    {
        $response = (new FileManagerApi)->delete($performance);

        if ($response->status() == 404)
            $performance->delete();

        return back()->with('status', 'The performance has been deleted.');
    }
}
