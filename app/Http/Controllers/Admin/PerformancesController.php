<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Performance;
use App\Events\Performances\PerformanceApproved;
use App\Cloudinary\CloudinaryApi;
use GuzzleHttp\Client;

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

    public function testConnection(Request $request)
    {
        $client = new Client([
            'headers' => ['Content-Type' => 'application/json']
        ]);
        return 'getting there';
        $response = $client->post('http://159.203.174.170/upload', ['body' => ''])->getBody();

        dd($response);
    }

    public function update(Request $request, Performance $performance)
    {
        $performance->update([
            'video_url' => $request->video_url,
            'thumbnail_url' => $request->thumbnail_url
        ]);

        return back()->with('status', 'The performance has been updated.');
    }

    public function getUrls(Request $request, Performance $performance)
    {
        if ($record = (new CloudinaryApi)->find($performance))
            $performance->update([
                'video_url' => $record['url'],
                'thumbnail_url' => (new CloudinaryApi)->getThumbnailFrom($record['url'])
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
        $response = (new CloudinaryApi)->delete($performance);

        if ($response['result'] == 'ok') {
            $performance->delete();

            return back()->with('status', 'The performance has been deleted.');
        }

        return back()->with('error', 'The performance could not be deleted.');
    }
}
