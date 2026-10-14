<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use App\Support\AdminPagination;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FolderReport
{
    public const SIZES = ['empty' => 'Empty', 'one' => '1 piece', 'small' => '2–5 pieces', 'medium' => '6–10 pieces', 'large' => '11+ pieces'];

    public function get(Request $request): array
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:160', 'size' => 'nullable|in:empty,one,small,medium,large',
            'sort' => 'nullable|in:newest,oldest,largest,name,owner,saved', 'folder' => 'nullable|integer|min:1',
            'page' => 'nullable|integer|min:1', 'pieces_page' => 'nullable|integer|min:1',
        ]);
        $filters = array_filter($filters, function ($value) { return $value !== null && $value !== ''; });
        if (isset($filters['search'])) {
            $filters['search'] = trim($filters['search']);
            if ($filters['search'] === '') unset($filters['search']);
        }
        $base = $this->folders();
        $summary = (array) DB::query()->fromSub(clone $base, 'folders')->selectRaw('COUNT(*) as total,
            COUNT(DISTINCT user_id) as owners, COALESCE(SUM(pieces_count), 0) as placements,
            COALESCE(SUM(CASE WHEN pieces_count > 0 THEN 1 ELSE 0 END), 0) as filled')->first();
        $summary = array_map('intval', $summary);
        $summary['eligible_users'] = $this->users()->count();
        $summary['adoption'] = $this->share($summary['owners'], $summary['eligible_users']);
        $summary['filled_share'] = $this->share($summary['filled'], $summary['total']);
        $summary['average'] = $summary['total'] ? round($summary['placements'] / $summary['total'], 1) : 0;
        $buckets = DB::query()->fromSub(clone $base, 'folders')->selectRaw("CASE
            WHEN pieces_count = 0 THEN 'empty' WHEN pieces_count = 1 THEN 'one'
            WHEN pieces_count <= 5 THEN 'small' WHEN pieces_count <= 10 THEN 'medium' ELSE 'large' END as size, COUNT(*) as total")
            ->groupBy('size')->pluck('total', 'size');
        $sizes = [];
        foreach (self::SIZES as $key => $label) $sizes[] = ['key' => $key, 'label' => $label,
            'total' => (int) ($buckets[$key] ?? 0), 'share' => $this->share($buckets[$key] ?? 0, $summary['total'])];
        $now = now();
        $start = $now->copy()->startOfMonth()->subMonths(11);
        $months = DB::query()->fromSub(clone $base, 'folders')->whereBetween('created_at', [$start, $now])
            ->selectRaw('SUBSTR(created_at, 1, 7) as month, COUNT(*) as total')->groupBy('month')->pluck('total', 'month');
        $growth = [];
        for ($index = 0; $index < 12; $index++) {
            $month = $start->copy()->addMonths($index);
            $growth[] = ['month' => $month->format('Y-m'), 'label' => $month->format('M Y'),
                'short' => $month->format('M'), 'total' => (int) ($months[$month->format('Y-m')] ?? 0)];
        }
        $owners = DB::query()->fromSub(clone $base, 'folders')->select('user_id', 'first_name', 'last_name')
            ->selectRaw('COUNT(*) as total, SUM(pieces_count) as pieces_count')->groupBy('user_id', 'first_name', 'last_name')
            ->orderByDesc('total')->orderByDesc('pieces_count')->orderBy('user_id')->limit(5)->get();
        $popular = DB::query()->fromSub(clone $base, 'folders')->join('favorites as saved', function ($join) {
            $join->on('saved.favorite_folder_id', '=', 'folders.id')->on('saved.user_id', '=', 'folders.user_id');
        })->join('pieces as p', 'p.id', '=', 'saved.piece_id')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->select('p.id', 'p.name', 'c.name as composer')->selectRaw('COUNT(DISTINCT folders.id) as total, COUNT(DISTINCT folders.user_id) as owners')
            ->groupBy('p.id', 'p.name', 'c.name')->orderByDesc('total')->orderByDesc('owners')->orderBy('p.id')->limit(5)->get();
        $rows = DB::query()->fromSub(clone $base, 'folders');
        if (isset($filters['search'])) {
            $term = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], trim($filters['search'])).'%';
            $rows->where(function ($query) use ($term, $filters) {
                $query->whereRaw("name LIKE ? ESCAPE '!'", [$term])->orWhereRaw("email LIKE ? ESCAPE '!'", [$term])
                    ->orWhere(function ($names) use ($filters) {
                        foreach (preg_split('/\s+/', trim($filters['search']), -1, PREG_SPLIT_NO_EMPTY) as $part) {
                            $word = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $part).'%';
                            $names->where(function ($name) use ($word) {
                                $name->whereRaw("first_name LIKE ? ESCAPE '!'", [$word])->orWhereRaw("last_name LIKE ? ESCAPE '!'", [$word]);
                            });
                        }
                    });
            });
        }
        switch ($filters['size'] ?? '') {
            case 'empty': $rows->where('pieces_count', 0); break;
            case 'one': $rows->where('pieces_count', 1); break;
            case 'small': $rows->whereBetween('pieces_count', [2, 5]); break;
            case 'medium': $rows->whereBetween('pieces_count', [6, 10]); break;
            case 'large': $rows->where('pieces_count', '>=', 11); break;
        }
        switch ($filters['sort'] ?? 'newest') {
            case 'oldest': $rows->orderByRaw('CASE WHEN created_at IS NULL THEN 1 ELSE 0 END')->orderBy('created_at'); break;
            case 'largest': $rows->orderByDesc('pieces_count'); break;
            case 'name': $rows->orderBy('name'); break;
            case 'owner': $rows->orderBy('first_name')->orderBy('last_name'); break;
            case 'saved': $rows->orderByDesc('last_saved_at'); break;
            default: $rows->orderByDesc('created_at');
        }
        $folders = $rows->orderByDesc('id')->paginate(AdminPagination::PER_PAGE)->appends($filters);
        $selected = null;
        $pieces = null;
        if (!empty($filters['folder'])) {
            $selected = DB::query()->fromSub(clone $base, 'folders')->where('id', $filters['folder'])->first();
            abort_unless($selected, 404);
            $pieces = DB::table('favorites as saved')->join('pieces as p', 'p.id', '=', 'saved.piece_id')
                ->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')->where('saved.favorite_folder_id', $selected->id)
                ->where('saved.user_id', $selected->user_id)->select('p.id', 'p.name', 'c.name as composer')
                ->selectRaw('MIN(saved.'.DB::connection()->getQueryGrammar()->wrap('order').') as position, MIN(saved.created_at) as saved_at')
                ->groupBy('p.id', 'p.name', 'c.name')->orderBy('position')->orderBy('p.id')
                ->paginate(AdminPagination::PER_PAGE, ['*'], 'pieces_page')->appends($filters)->fragment('folder-detail');
        }
        return compact('summary', 'sizes', 'growth', 'owners', 'popular', 'folders', 'filters', 'selected', 'pieces');
    }

    private function users()
    {
        return ExcludedAccounts::users(DB::table('users as u'), 'u.email')->whereNotIn('u.id', User::team()->select('id'));
    }

    private function folders()
    {
        // Count existing pieces belonging to the folder's own account once per folder.
        $saved = DB::table('favorites as saved')->join('pieces as p', 'p.id', '=', 'saved.piece_id')
            ->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')->select('saved.favorite_folder_id', 'saved.user_id')
            ->selectRaw('COUNT(DISTINCT p.id) as pieces_count, COUNT(DISTINCT c.id) as composers_count, MAX(saved.created_at) as last_saved_at')
            ->groupBy('saved.favorite_folder_id', 'saved.user_id');
        return $this->users()->join('favorite_folders as f', 'f.user_id', '=', 'u.id')->where('f.is_default', false)
            ->leftJoinSub($saved, 'contents', function ($join) {
                $join->on('contents.favorite_folder_id', '=', 'f.id')->on('contents.user_id', '=', 'f.user_id');
            })->select('f.id', 'f.user_id', 'f.name', 'f.description', 'f.created_at', 'u.first_name', 'u.last_name', 'u.email', 'contents.last_saved_at')
            ->selectRaw('COALESCE(contents.pieces_count, 0) as pieces_count, COALESCE(contents.composers_count, 0) as composers_count');
    }

    private function share($count, $total): float
    {
        return $total ? round($count / $total * 100, 1) : 0;
    }
}
