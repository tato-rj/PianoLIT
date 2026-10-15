<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\Services\Admin\FeedbackTable;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function index(Request $request, FeedbackTable $table)
    {
        return view('admin.pages.feedback.index', $table->get($request));
    }

    public function destroy(Request $request, FeedbackSubmission $feedback)
    {
        $table = app(FeedbackTable::class);
        $filters = $table->filters($request);
        $feedback->delete();

        if ($request->ajax()) {
            return response()->json(['deleted' => true]);
        }

        $remaining = $table->query($filters)->count();
        $lastPage = max(1, (int) ceil($remaining / $filters['per_page']));
        $filters['page'] = min((int) ($filters['page'] ?? 1), $lastPage);

        return redirect()->route('admin.feedback.index', AdminTableFilters::query($filters))
            ->with('status', 'Feedback deleted.');
    }
}
