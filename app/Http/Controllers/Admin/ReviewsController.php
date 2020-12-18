<?php

namespace App\Http\Controllers\Admin;

use App\Review;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ReviewsController extends Controller
{
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
