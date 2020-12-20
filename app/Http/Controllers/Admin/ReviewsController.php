<?php

namespace App\Http\Controllers\Admin;

use App\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReviewsController extends Controller
{
    public function index()
    {
        if (request()->ajax())
            return Review::datatable();

        return view('admin.pages.reviews.index');
    }

    public function updateStatus(Review $review)
    {
        return $this->updateStatusFor($review);
    }

    public function store(Request $request)
    {
        $request->validate(['rating' => 'required']);
        
        $product = $request->model::find($request->id);

        Review::create([
            'rating' => $request->rating,
            'reviewable_type' => get_class($product),
            'reviewable_id' => $product->id,
            'title' => $request->title,
            'reviewer' => $request->reviewer,
            'content' => $request->content,
            'published_at' => now()
        ]);

        return back()->with('status', 'Your review has been successfully submited.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Merchandise\Review  $review
     * @return \Illuminate\Http\Response
     */
    public function destroy(Review $review)
    {
        $review->delete();

        return back()->with('status', 'The review has been successfully deleted.');
    }
}
