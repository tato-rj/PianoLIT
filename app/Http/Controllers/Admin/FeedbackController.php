<?php

namespace App\Http\Controllers\Admin;

use App\FeedbackSubmission;
use App\Http\Controllers\Controller;
use App\Support\AdminPagination;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\EloquentDataTable;

class FeedbackController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'rating' => ['nullable', 'string', Rule::in(array_keys(FeedbackSubmission::RATINGS))],
        ]);
        $rating = $data['rating'] ?? null;
        $query = FeedbackSubmission::with(['user' => function ($query) {
            $query->select('id', 'first_name', 'last_name', 'email');
        }])->when($rating, function ($query) use ($rating) {
            $query->where('rating', $rating);
        });

        if ($request->ajax()) {
            return $this->datatable($request, $query, $rating);
        }

        $submissions = $query->latest()->orderByDesc('id')->paginate(AdminPagination::PER_PAGE)->withQueryString();

        return view('admin.pages.feedback.index', compact('submissions', 'rating'));
    }

    protected function datatable(Request $request, $query, $rating)
    {
        $request->validate([
            'draw' => 'sometimes|integer|min:0',
            'start' => 'sometimes|integer|min:0|max:10000000',
            'length' => 'sometimes|integer|min:1|max:96',
            'search' => 'sometimes|array',
            'search.value' => 'nullable|string|max:255',
            'order' => 'sometimes|array|max:4',
            'order.*.column' => 'required|integer|between:0,3',
            'order.*.dir' => 'required|in:asc,desc',
            'columns' => 'sometimes|array|max:5',
            'columns.*' => 'required|array',
            'columns.*.data' => 'required|string|max:100',
            'columns.*.name' => 'nullable|string|max:100',
            'columns.*.search' => 'sometimes|array',
            'columns.*.search.value' => 'nullable|string|max:255',
        ]);
        $request->merge([
            'start' => $request->input('start', 0),
            'length' => $request->input('length', AdminPagination::PER_PAGE),
        ]);

        // Only these fixed fields may participate in SQL; submitted column names
        // and per-column search expressions never become query instructions.
        $table = (new EloquentDataTable($query))->makeHidden(['user'])
            ->only(['submitted', 'rating', 'message', 'sender', 'actions'])
            ->whitelist([])
            ->filter(function ($query) use ($request) {
                foreach (preg_split('/\s+/u', trim((string) $request->input('search.value', ''))) as $term) {
                    if ($term === '') continue;
                    $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term).'%';
                    $ratings = array_keys(array_filter(FeedbackSubmission::RATINGS, function ($label) use ($term) {
                        return stripos($label, $term) !== false;
                    }));
                    $query->where(function ($query) use ($like, $ratings, $term) {
                        $query->whereRaw("message LIKE ? ESCAPE '!'", [$like])
                            ->orWhereIn('rating', $ratings)
                            ->orWhereHas('user', function ($user) use ($like) {
                                $user->whereRaw("first_name LIKE ? ESCAPE '!'", [$like])
                                    ->orWhereRaw("last_name LIKE ? ESCAPE '!'", [$like])
                                    ->orWhereRaw("email LIKE ? ESCAPE '!'", [$like]);
                            });
                        if (stripos('Guest', $term) !== false) $query->orWhereDoesntHave('user');
                    });
                }
            })
            ->order(function ($query) use ($request) {
                $orders = $request->input('order', [['column' => 0, 'dir' => 'desc']]);
                foreach ($orders as $order) {
                    if ((int) $order['column'] === 3) {
                        foreach (['first_name', 'last_name', 'email'] as $field) {
                            $query->orderBy(\App\User::select($field)->whereColumn('users.id', 'feedback_submissions.user_id'), $order['dir']);
                        }
                    } else {
                        $query->orderBy(['created_at', 'rating', 'message'][(int) $order['column']], $order['dir']);
                    }
                }
                $query->orderByDesc('id');
            });

        $page = (int) floor((int) $request->input('start', 0) / AdminPagination::PER_PAGE) + 1;
        foreach (['submitted', 'rating', 'message', 'sender', 'actions'] as $column) {
            $table->addColumn($column, function ($submission) use ($column, $rating, $page) {
                return view('admin.pages.feedback.table.'.$column, compact('submission', 'rating', 'page'))->render();
            });
        }

        return $table->rawColumns(['submitted', 'rating', 'message', 'sender', 'actions'])->make(true);
    }

    public function destroy(Request $request, FeedbackSubmission $feedback)
    {
        $data = $request->validate([
            'rating' => ['nullable', 'string', Rule::in(array_keys(FeedbackSubmission::RATINGS))],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $rating = $data['rating'] ?? null;
        $feedback->delete();

        if ($request->ajax()) {
            return response()->json(['deleted' => true]);
        }

        $remaining = FeedbackSubmission::when($rating, function ($query) use ($rating) {
            $query->where('rating', $rating);
        })->count();
        $lastPage = max(1, (int) ceil($remaining / AdminPagination::PER_PAGE));
        $page = min((int) ($data['page'] ?? 1), $lastPage);

        return redirect()->route('admin.feedback.index', [
            'rating' => $rating,
            'page' => $page > 1 ? $page : null,
        ])->with('status', 'Feedback deleted.');
    }
}
