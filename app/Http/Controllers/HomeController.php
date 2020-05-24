<?php

namespace App\Http\Controllers;

use App\{Tag, Piece};
use Illuminate\Http\Request;
use App\Jobs\UploadToCloud;
use App\Rules\VideoLength;

class HomeController extends Controller
{
    public function filetest(Request $request)
    {
        \Validator::make($request->all(), [
            'video' => 'required|mimetypes:video/avi,video/mpeg,video/mp4,video/x-ms-wmv,video/x-msvideo,video/3gpp,video/quicktime|max:30000'
        ],[
            'max' => 'The video size cannot exceed 30MB.',
        ])->validate();

        $file = $request->file('video');

        return response()->json('Your video is being processed');
        // UploadToCloud::dispatchNow($file);
    }
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $latest = Piece::latest()->with(['composer', 'tags'])->take(8)->get();
        $tags = Tag::byTypes(['ranking', 'length', 'period', 'genre'])->reverse();
        $testimonials = testimonials();
        shuffle($testimonials);

        return view('home.index', compact(['latest', 'tags', 'testimonials']));
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
            'view' => ! $pieces->isEmpty() ? view('components.pieces.display', compact('pieces'))->render() : view('components.pieces.empty')->render(),
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
