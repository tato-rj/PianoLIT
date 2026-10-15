<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use App\Composer;
use App\User;
use App\Support\AdminPagination;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ComposerReport
{
    public const DEPTHS = ['empty' => 'No pieces', 'small' => '1–3 pieces', 'medium' => '4–10 pieces', 'large' => '11+ pieces'];
    private const COLORS = ['#a692ce', '#94c6b0', '#efb07c', '#83c9df', '#e8a6c0', '#e8ce87'];

    public function get(Request $request): array
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:160'],
            'depth' => ['nullable', 'string', Rule::in(array_keys(self::DEPTHS))],
            'period' => ['nullable', 'string', 'max:100'],
            'profile' => ['nullable', 'string', Rule::in(['biography', 'portrait', 'country'])],
            'coverage' => ['nullable', 'string', Rule::in(['videos', 'folders', 'playlists'])],
            'sort' => ['sometimes', 'string', Rule::in(['pieces', 'views', 'saves', 'name', 'newest', 'birth'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);
        $now = now();
        $pieces = DB::table('pieces')->select('composer_id')->selectRaw("COUNT(*) as total,
            SUM(CASE WHEN EXISTS (SELECT 1 FROM tutorials video WHERE video.piece_id = pieces.id
                AND COALESCE(TRIM(video.video_url), '') <> '') THEN 1 ELSE 0 END) as video_pieces")->groupBy('composer_id');
        $folders = ExcludedAccounts::users(DB::table('users as folder_owner'), 'folder_owner.email')
            ->whereNotIn('folder_owner.id', User::team()->select('id'))
            ->join('favorite_folders as folder', 'folder.user_id', '=', 'folder_owner.id')->where('folder.is_default', false)
            ->join('favorites as folder_save', function ($join) {
                $join->on('folder_save.favorite_folder_id', '=', 'folder.id')->on('folder_save.user_id', '=', 'folder.user_id');
            })->join('pieces as folder_piece', 'folder_piece.id', '=', 'folder_save.piece_id')
            ->select('folder_piece.composer_id')->distinct();
        $playlists = $this->collectionComposers($now);
        // Read scalar metadata once. Never serialize Composer/User appended attributes.
        $rows = DB::table('composers as c')->leftJoin('countries as country', 'country.id', '=', 'c.country_id')
            ->leftJoinSub($pieces, 'pieces', 'pieces.composer_id', '=', 'c.id')
            ->leftJoinSub($folders, 'folders', 'folders.composer_id', '=', 'c.id')
            ->leftJoinSub($playlists, 'collections', 'collections.composer_id', '=', 'c.id')
            ->select('c.id', 'c.name', 'c.period', 'c.gender', 'c.date_of_birth', 'c.date_of_death', 'c.created_at',
                'country.id as country_record_id', 'country.name as country_name', 'country.flag_code as country_code')
            ->selectRaw("COALESCE(pieces.total, 0) as pieces, COALESCE(pieces.video_pieces, 0) as video_pieces,
                CASE WHEN folders.composer_id IS NOT NULL THEN 1 ELSE 0 END as folders,
                CASE WHEN collections.composer_id IS NOT NULL THEN 1 ELSE 0 END as playlists,
                CASE WHEN COALESCE(TRIM(c.biography), '') <> '' THEN 1 ELSE 0 END as biography,
                CASE WHEN COALESCE(TRIM(c.cover_path), '') <> '' THEN 1 ELSE 0 END as portrait")->orderBy('c.id')->get();
        $views = ExcludedAccounts::owned(DB::table('piece_views as v'), 'v.user_id')
            ->join('pieces as p', 'p.id', '=', 'v.piece_id')->select('p.composer_id')->selectRaw('COUNT(*) as total')->groupBy('p.composer_id')->get()->keyBy('composer_id');
        // The same account saving the same piece in several folders is one save.
        $saved = ExcludedAccounts::users(DB::table('favorites as f')->join('users as u', 'u.id', '=', 'f.user_id'), 'u.email')
            ->select('f.user_id', 'f.piece_id')->distinct();
        $saves = DB::query()->fromSub($saved, 'saved')->join('pieces as p', 'p.id', '=', 'saved.piece_id')
            ->select('p.composer_id')->selectRaw('COUNT(*) as total, COUNT(DISTINCT saved.user_id) as users')->groupBy('p.composer_id')->get()->keyBy('composer_id');

        $world = json_decode(file_get_contents(resource_path('data/admin-world-map.json')), true, 512, JSON_THROW_ON_ERROR);
        $countryNames = json_decode(file_get_contents(resource_path('data/country-continents.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($world['countries'] as $shape) $countryNames[$shape['code']]['name'] = $shape['name'];

        $composers = $rows->map(function ($row) use ($views, $saves, $countryNames, $now) {
            $row = (array) $row;
            $row['id'] = (int) $row['id']; $row['pieces'] = (int) $row['pieces'];
            $row['biography'] = (bool) $row['biography']; $row['portrait'] = (bool) $row['portrait'];
            $row['videos'] = $row['video_pieces'] > 0;
            $row['folders'] = (bool) $row['folders']; $row['playlists'] = (bool) $row['playlists'];
            $row['period'] = strtolower(trim($row['period'] ?? ''));
            $row['gender'] = strtolower(trim($row['gender'] ?? ''));
            $row['country_code'] = strtoupper(trim($row['country_code'] ?? ''));
            // A saved country exists independently of the map's code/geometry lookup.
            $row['country'] = $row['country_record_id'] !== null
                ? (trim((string) $row['country_name']) ?: 'Country #'.$row['country_record_id']) : null;
            $row['country_key'] = isset($countryNames[$row['country_code']]) ? $row['country_code'] : 'country-'.$row['country_record_id'];
            $row['views'] = (int) ($views[$row['id']]->total ?? 0);
            $row['saves'] = (int) ($saves[$row['id']]->total ?? 0);
            $row['savers'] = (int) ($saves[$row['id']]->users ?? 0);
            $row['saves_per_piece'] = $row['pieces'] ? round($row['saves'] / $row['pieces'], 1) : 0.0;
            $row['depth'] = !$row['pieces'] ? 'empty' : ($row['pieces'] <= 3 ? 'small' : ($row['pieces'] <= 10 ? 'medium' : 'large'));
            return $row + $this->dates($row, $now);
        });
        $total = $composers->count();
        $summary = ['total' => $total, 'pieces' => (int) $composers->sum('pieces'), 'represented' => $composers->where('pieces', '>', 0)->count(),
            'small' => $composers->where('depth', 'small')->count(), 'empty' => $composers->where('depth', 'empty')->count(),
            'biography' => $composers->where('biography', true)->count(), 'portrait' => $composers->where('portrait', true)->count(),
            'videos' => $composers->where('videos', true)->count(), 'folders' => $composers->where('folders', true)->count(),
            'playlists' => $composers->where('playlists', true)->count(),
            'located' => $composers->whereNotNull('country')->count()];
        $summary['coverage'] = $this->share($summary['represented'], $total);
        $summary['top_share'] = $this->share($composers->sortByDesc('pieces')->take(5)->sum('pieces'), $summary['pieces']);
        $depths = [];
        foreach (self::DEPTHS as $key => $label) $depths[] = ['key' => $key, 'label' => $label, 'total' => $composers->where('depth', $key)->count(), 'color' => self::COLORS[count($depths)]];
        $periodKeys = array_values(array_unique(array_merge(periods(), $composers->pluck('period')->filter()->sort()->all())));
        $periods = [];
        foreach (array_merge($periodKeys, ['']) as $key) {
            $group = $composers->where('period', $key);
            if ($key === '' && !$group->count()) continue;
            $periods[] = ['key' => $key, 'label' => $key === '' ? 'Period not recorded' : ucfirst($key), 'total' => $group->count(), 'videos' => $group->where('videos', true)->count()];
        }
        $genders = [];
        foreach ($composers->groupBy('gender')->sortKeys() as $key => $group) $genders[] = ['label' => $key === '' ? 'Not recorded' : ucfirst($key), 'total' => $group->count(), 'color' => self::COLORS[count($genders) % count(self::COLORS)]];
        $growth = $this->growth($composers, $now);
        $countries = $this->countries($composers, $world);
        $rankings = [];
        foreach (['views', 'saves'] as $metric) $rankings[$metric] = $this->rank($composers->where($metric, '>', 0), $metric)->take(6)->values()->all();
        $opportunities = $this->rank($composers->where('depth', 'small')->where('saves', '>', 0), 'saves_per_piece')->take(5)->values()->all();
        $anniversaries = [];
        foreach ($composers as $composer) {
            foreach (['birth' => 'Birthday', 'death' => 'Death anniversary'] as $type => $label) {
                if (!$composer[$type.'_exact']) continue;
                $date = $composer['date_of_'.$type];
                for ($day = 0; $day < 30; $day++) {
                    $next = $now->copy()->startOfDay()->addDays($day);
                    if ($next->format('m-d') !== substr($date, 5, 5)) continue;
                    $anniversaries[] = ['id' => $composer['id'], 'name' => $composer['name'], 'type' => $label, 'date' => $next,
                        'years' => $next->year - (int) substr($date, 0, 4), 'pieces' => $composer['pieces']];
                }
            }
        }
        usort($anniversaries, function ($a, $b) { return ($a['date'] <=> $b['date']) ?: ($a['id'] <=> $b['id']) ?: strcmp($a['type'], $b['type']); });

        $directory = $composers->filter(function ($row) use ($filters) {
            if (!empty($filters['search']) && mb_stripos($row['name'], trim($filters['search'])) === false) return false;
            if (!empty($filters['depth']) && $row['depth'] !== $filters['depth']) return false;
            if (isset($filters['period']) && $filters['period'] !== '' && ($row['period'] ?: '__missing') !== $filters['period']) return false;
            if (!empty($filters['profile']) && $row[$filters['profile']]) return false;
            if (!empty($filters['coverage']) && $row[$filters['coverage']]) return false;
            return true;
        });
        $sort = $filters['sort'] ?? 'pieces';
        if (in_array($sort, ['pieces', 'views', 'saves'], true)) $directory = $this->rank($directory, $sort);
        else $directory = $directory->sort(function ($a, $b) use ($sort) {
            if ($sort === 'newest') $comparison = strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
            elseif ($sort === 'birth') $comparison = strcmp($a['birth_exact'] ? $a['date_of_birth'] : '9999', $b['birth_exact'] ? $b['date_of_birth'] : '9999');
            else $comparison = strnatcasecmp($a['name'], $b['name']);
            return $comparison ?: ($a['id'] <=> $b['id']);
        });
        $directory = new LengthAwarePaginator($directory->values()->forPage((int) ($filters['page'] ?? 1), AdminPagination::PER_PAGE)->values(), $directory->count(), AdminPagination::PER_PAGE,
            (int) ($filters['page'] ?? 1), ['path' => route('admin.analytics.composers'), 'query' => $filters]);
        return compact('summary', 'depths', 'periods', 'periodKeys', 'genders', 'growth', 'countries', 'rankings', 'opportunities', 'anniversaries', 'directory', 'filters');
    }

    private function share($count, $total): float { return $total ? round($count / $total * 100, 1) : 0.0; }

    private function collectionComposers($now)
    {
        // Mirror Collections::data eligibility and the playlist player's displayed pieces.
        // Tutorial presence determines collection eligibility; a saved video URL is
        // required separately for the Videos gauge, matching PieceReport.
        $counts = DB::table('piece_playlist as listed')->join('pieces as listed_piece', 'listed_piece.id', '=', 'listed.piece_id')
            ->select('listed.playlist_id')->selectRaw('COUNT(*) as total,
                SUM(CASE WHEN EXISTS (SELECT 1 FROM tutorials WHERE tutorials.piece_id = listed_piece.id) THEN 1 ELSE 0 END) as with_tutorials')
            ->groupBy('listed.playlist_id');
        $eligible = DB::table('playlists as playlist')->joinSub($counts, 'counts', 'counts.playlist_id', '=', 'playlist.id')
            ->whereNull('playlist.group')->whereNotNull('playlist.published_at')->where('playlist.published_at', '<=', $now)
            ->where('counts.total', '>', 5)->where('counts.with_tutorials', '>=', 5)->select('playlist.id');
        return DB::table('piece_playlist as listed')->joinSub($eligible, 'eligible', 'eligible.id', '=', 'listed.playlist_id')
            ->join('pieces as listed_piece', 'listed_piece.id', '=', 'listed.piece_id')
            ->whereExists(function ($query) { $query->selectRaw('1')->from('tutorials')->whereColumn('tutorials.piece_id', 'listed_piece.id'); })
            ->select('listed_piece.composer_id')->distinct();
    }

    private function rank($rows, string $key)
    {
        return $rows->sort(function ($a, $b) use ($key) { return ($b[$key] <=> $a[$key]) ?: ($a['id'] <=> $b['id']); });
    }

    private function dates(array $row, $now): array
    {
        $model = (new Composer)->setRawAttributes(['id' => $row['id']]);
        $result = [];
        foreach (['birth', 'death'] as $type) {
            $raw = $row['date_of_'.$type];
            $unknown = $type === 'birth' ? $model->unknownBirthday() : $model->unknownDeathday();
            $date = null;
            try {
                if ($raw) {
                    $parsed = Carbon::createFromFormat('!Y-m-d', substr($raw, 0, 10));
                    if ($parsed->format('Y-m-d') === substr($raw, 0, 10) && $parsed->year > 0 && $parsed->lte($now)) $date = $parsed;
                }
            } catch (\Throwable $exception) { /* Invalid legacy dates stay unknown. */ }
            if ($type === 'death' && $date && !empty($result['birth_exact']) && $raw < $row['date_of_birth']) $date = null;
            $result[$type.'_exact'] = $date !== null && !$unknown;
            $result[$type.'_label'] = $unknown ?: ($date ? $date->format('M j, Y') : ($raw ? 'Invalid date' : 'Not recorded'));
        }
        $result['age'] = null;
        if ($result['birth_exact'] && (!$row['date_of_death'] || $result['death_exact'])) {
            $result['age'] = Carbon::parse($row['date_of_birth'])->diffInYears($result['death_exact'] ? Carbon::parse($row['date_of_death']) : $now);
        }
        return $result;
    }

    private function growth($rows, $now): array
    {
        $counts = $rows->filter(function ($row) use ($now) { return $row['created_at'] && $row['created_at'] <= $now->toDateTimeString(); })
            ->groupBy(function ($row) { return substr($row['created_at'], 0, 7); });
        $start = $now->copy()->startOfMonth()->subMonths(11);
        $result = [];
        for ($index = 0; $index < 12; $index++) {
            $month = $start->copy()->addMonths($index);
            $result[] = ['month' => $month->format('Y-m'), 'label' => $month->format('M Y'), 'short' => $month->format('M'), 'total' => isset($counts[$month->format('Y-m')]) ? $counts[$month->format('Y-m')]->count() : 0];
        }
        return $result;
    }

    private function countries($rows, array $world): array
    {
        $shapes = array_column($world['countries'], null, 'code');
        $countries = [];
        foreach ($rows->whereNotNull('country')->groupBy('country_key') as $code => $group) {
            $countries[] = ['code' => $code, 'name' => $group->first()['country'], 'total' => $group->count(), 'has_shape' => isset($shapes[$code])];
        }
        usort($countries, function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']); });
        $located = array_sum(array_column($countries, 'total'));
        foreach ($countries as $index => &$country) { $country['share'] = $this->share($country['total'], $located); $country['color'] = $index < 6 ? (string) $index : 'other'; }
        unset($country);
        $mapped = array_sum(array_column(array_filter($countries, function ($country) { return $country['has_shape']; }), 'total'));
        return ['total' => $rows->count(), 'located' => $located, 'unlocated' => $rows->count() - $located,
            'mapped' => $mapped, 'not_on_map' => $located - $mapped, 'countries' => $countries,
            'byCode' => array_column($countries, null, 'code'), 'top' => $countries[0] ?? null, 'world' => $world];
    }
}
