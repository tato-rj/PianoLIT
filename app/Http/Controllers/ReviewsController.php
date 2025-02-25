<?php

namespace App\Http\Controllers;

use App\{Review, Admin};
use Illuminate\Http\Request;
use App\Http\Requests\ReviewsForm;
use App\Notifications\ReviewSubmittedNotification;

class ReviewsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ReviewsForm $form, $model, $id)
    {
        $review = auth()->user()->reviews()->create([
            'rating' => $form->rating,
            'reviewable_type' => get_class($form->product),
            'reviewable_id' => $form->product->id,
            'title' => $form->title,
            'reviewer' => $form->reviewer,
            'content' => $form->content
        ]);

        Admin::notifyAll(new ReviewSubmittedNotification($review));

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
        $this->authorize('update', $review);

        $review->delete();

        return back()->with('status', 'The review has been successfully deleted.');
    }
}
