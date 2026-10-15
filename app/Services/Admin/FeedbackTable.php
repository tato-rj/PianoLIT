<?php

namespace App\Services\Admin;

use App\{FeedbackSubmission, User};
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FeedbackTable
{
    const SORTS = [
        'date_desc' => 'Submitted · newest', 'date_asc' => 'Submitted · oldest',
        'rating_asc' => 'Rating · A–Z', 'rating_desc' => 'Rating · Z–A',
        'message_asc' => 'Message · A–Z', 'message_desc' => 'Message · Z–A',
        'sender_asc' => 'Sender · A–Z', 'sender_desc' => 'Sender · Z–A',
    ];

    public function filters(Request $request)
    {
        return AdminTableFilters::fromRequest($request, self::SORTS, 'date_desc', [
            'rating' => ['nullable', 'string', Rule::in(array_keys(FeedbackSubmission::RATINGS))],
        ]);
    }

    public function get(Request $request)
    {
        $filters = $this->filters($request);
        $returnFilters = AdminTableFilters::query(array_diff_key($filters, ['page' => true]));
        $submissions = $this->query($filters)->with(['user:id,first_name,last_name,email'])
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)->appends($returnFilters);

        return ['submissions' => $submissions, 'filters' => $filters,
            'rating' => $filters['rating'] ?? null, 'returnFilters' => $returnFilters];
    }

    public function query(array $filters)
    {
        $query = FeedbackSubmission::when($filters['rating'] ?? null, function ($query, $rating) {
            $query->where('rating', $rating);
        });
        foreach (preg_split('/\s+/u', $filters['search']) as $term) {
            if ($term === '') continue;
            $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], mb_strtolower($term)).'%';
            $ratings = array_keys(array_filter(FeedbackSubmission::RATINGS, function ($label) use ($term) {
                return stripos($label, $term) !== false;
            }));
            $query->where(function ($query) use ($like, $ratings, $term) {
                $query->whereRaw("LOWER(message) LIKE ? ESCAPE '!'", [$like])->orWhereIn('rating', $ratings)
                    ->orWhereHas('user', function ($user) use ($like) {
                        $user->where(function ($names) use ($like) {
                            foreach (['first_name', 'last_name', 'email'] as $field) {
                                $names->orWhereRaw("LOWER({$field}) LIKE ? ESCAPE '!'", [$like]);
                            }
                        });
                    });
                if (stripos('Guest', $term) !== false) $query->orWhereDoesntHave('user');
            });
        }

        [$sort, $direction] = explode('_', $filters['sort']);
        if ($sort === 'sender') {
            foreach (['first_name', 'last_name', 'email'] as $field) {
                $query->orderBy(User::select($field)->whereColumn('users.id', 'feedback_submissions.user_id'), $direction);
            }
        } else {
            $query->orderBy(['date' => 'created_at', 'rating' => 'rating', 'message' => 'message'][$sort], $direction);
        }

        return $query->orderByDesc('id');
    }
}
