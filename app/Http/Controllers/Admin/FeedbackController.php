<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'rating' => ['nullable', 'string', Rule::in(array_keys(FeedbackSubmission::RATINGS))],
        ]);
        $rating = $data['rating'] ?? null;
        $submissions = FeedbackSubmission::with(['user' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'email');
        }])->when($rating, function ($query) use ($rating) {
            $query->where('rating', $rating);
        })->latest()->orderByDesc('id')->paginate(AdminPagination::PER_PAGE)->withQueryString();

        return view('admin.pages.feedback.index', compact('submissions', 'rating'));
    }
}
