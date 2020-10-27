<?php

namespace App\Http\Controllers;

use App\{Tag, Piece};
use App\Api\Api;
use Illuminate\Http\Request;
use App\Jobs\UploadToCloud;
use App\Rules\VideoLength;

class HomeController extends Controller
{
    public function filetest(Request $request)
    {
        \Validator::make($request->all(), [
            'video' => 'required|mimetypes:video/avi,video/mpeg,video/mp4,video/x-ms-wmv,video/x-msvideo,video/3gpp,video/quicktime|max:'.config('filesystems.disks.gcs.max_sizes.video')
        ],[
            'max' => 'The video size cannot exceed 100MB.',
        ])->validate();

        UploadToCloud::dispatchNow($request->file('video'));

        return response(200);
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Api $api)
    {
        $latest = Piece::latest()->with(['composer', 'tags'])->take(8)->get();
        $tags = Tag::byTypes($except = ['sublevel', 'ranking', 'level', 'length', 'period', 'genre'])->reverse();
        $highlights = \App\Piece::freePicks()->free(false)->get();
        $composers = \App\Composer::inRandomOrder()->take(25)->get();
        $suggestions = $api->querySuggestions()['queries'];
        $testimonials = testimonials();
        shuffle($testimonials);
        
        return view('home.index', compact(['latest', 'tags', 'testimonials', 'composers', 'highlights', 'suggestions']));
    }

    public function loadPieces(Request $request)
    {
        if ($request->names) {
            $query = Piece::search(implode(' ', $request->names))->get();
            $total = $query->count();
            $pieces = $query->shuffle()->take(8);
            $label = 'Showing ' . $pieces->count() . ' of ' . $total . ' ' . str_plural('piece', $total) . ' found';
        } else {
            $query = Piece::latest()->with(['composer', 'tags']);
            $total = $query->count();
            $pieces = $query->take(8)->get();
            $label = 'Latest pieces';
        }
        
        return [
            'view' => view('search.components.results.pieces', compact('pieces'))->render(),
            'total' => $total,
            'count' => $pieces->count(),
            'label' => $label
        ];
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function contact()
    {
        return view('contact.index');
    }
}
