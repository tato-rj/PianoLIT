<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use App\Composer;
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
            'sort' => ['sometimes', 'string', Rule::in(['pieces', 'views', 'saves', 'name', 'newest', 'birth'])],
            'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
        ]);
        $now = now();
        $pieces = DB::table('pieces')->select('composer_id')->selectRaw('COUNT(*) as total')->groupBy('composer_id');
        // Read scalar metadata once. Never serialize Composer/User appended attributes.
        $rows = DB::table('composers as c')->leftJoin('countries as country', 'country.id', '=', 'c.country_id')
            ->leftJoinSub($pieces, 'pieces', 'pieces.composer_id', '=', 'c.id')
            ->select('c.id', 'c.name', 'c.period', 'c.gender', 'c.date_of_birth', 'c.date_of_death', 'c.created_at', 'country.flag_code as country_code')
            ->selectRaw("COALESCE(pieces.total, 0) as pieces, CASE WHEN COALESCE(TRIM(c.biography), '') <> '' THEN 1 ELSE 0 END as biography,
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
            $row['period'] = strtolower(trim($row['period'] ?? ''));
            $row['gender'] = strtolower(trim($row['gender'] ?? ''));
            $row['country_code'] = strtoupper(trim($row['country_code'] ?? ''));
            $row['country'] = $countryNames[$row['country_code']]['name'] ?? null;
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
            $periods[] = ['key' => $key, 'label' => $key === '' ? 'Period not recorded' : ucfirst($key), 'total' => $group->count(), 'represented' => $group->where('pieces', '>', 0)->count()];
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
        $countries = [];
        foreach ($rows->whereNotNull('country')->groupBy('country_code') as $code => $group) $countries[] = ['code' => $code, 'name' => $group->first()['country'], 'total' => $group->count()];
        usort($countries, function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']); });
        $located = array_sum(array_column($countries, 'total'));
        foreach ($countries as $index => &$country) { $country['share'] = $this->share($country['total'], $located); $country['color'] = $index < 6 ? (string) $index : 'other'; }
        unset($country);
        return ['total' => $rows->count(), 'located' => $located, 'unlocated' => $rows->count() - $located, 'countries' => $countries,
            'byCode' => array_column($countries, null, 'code'), 'top' => $countries[0] ?? null, 'world' => $world];
    }
}
