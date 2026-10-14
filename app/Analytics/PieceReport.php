<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PieceReport
{
    private const COLORS = ['#a692ce', '#94c6b0', '#efb07c', '#83c9df', '#e8a6c0', '#e8ce87', '#bfd6bb'];

    // Presence in the catalogue is not a check of storage or remote URL health.
    private function audio(): string { return "COALESCE(TRIM(p.audio_path), '') <> ''"; }
    private function score(): string { return "(COALESCE(TRIM(p.score_path), '') <> '' OR COALESCE(TRIM(p.score_url), '') <> '')"; }
    private function video(): string { return "EXISTS (SELECT 1 FROM tutorials v WHERE v.piece_id = p.id AND COALESCE(TRIM(v.video_url), '') <> '')"; }
    private function complete(): string { return '('.$this->audio().' AND '.$this->score().' AND '.$this->video().')'; }

    public function get(Request $request): array
    {
        $input = $request->validate(['months' => ['sometimes', 'integer', Rule::in([12, 24, 36])]]);
        $months = (int) ($input['months'] ?? 12);
        $now = now();
        $summary = (array) DB::table('pieces as p')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->selectRaw('COUNT(*) as total, COUNT(DISTINCT c.id) as composers,
                SUM(CASE WHEN c.id IS NULL THEN 1 ELSE 0 END) as missing_composer,
                SUM(CASE WHEN '.$this->audio().' THEN 1 ELSE 0 END) as audio,
                SUM(CASE WHEN '.$this->score().' THEN 1 ELSE 0 END) as score,
                SUM(CASE WHEN '.$this->video().' THEN 1 ELSE 0 END) as video,
                SUM(CASE WHEN '.$this->complete().' THEN 1 ELSE 0 END) as complete')->first();
        $summary = array_map('intval', $summary);
        $total = $summary['total'];
        $levels = $this->distribution('level', ['elementary', 'beginner', 'intermediate', 'advanced'], $total);
        $periods = $this->distribution('period', periods(), $total);
        $lengths = $this->distribution('length', lengths(), $total);
        $genderRows = DB::table('pieces as p')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')
            ->selectRaw("COALESCE(NULLIF(LOWER(TRIM(c.gender)), ''), '__missing') as label, COUNT(*) as total")
            ->groupByRaw("COALESCE(NULLIF(LOWER(TRIM(c.gender)), ''), '__missing')")->get();
        $genders = $this->categories($genderRows, ['female', 'male'], 'gender', $total);
        $composers = DB::table('pieces as p')->join('composers as c', 'c.id', '=', 'p.composer_id')
            ->select('c.id', 'c.name')->selectRaw('COUNT(*) as total')->groupBy('c.id', 'c.name')
            ->orderByDesc('total')->orderBy('c.name')->orderBy('c.id')->limit(8)->get()->map(function ($row) use ($total) {
                return ['id' => $row->id, 'label' => $row->name, 'total' => (int) $row->total, 'share' => $this->share($row->total, $total)];
            })->all();

        $growth = $this->growth($months, $now);
        $media = [];
        foreach (['score' => ['Score available', 'Uploaded score or external score link', '#a692ce'],
            'audio' => ['Full audio', 'A full-piece audio recording is saved', '#83c9df'],
            'video' => ['Video available', 'At least one video has a saved URL', '#efb07c'],
            'complete' => ['All three media', 'Score, full audio and video on the same piece', '#94c6b0']] as $key => $definition) {
            $media[] = ['label' => $definition[0], 'description' => $definition[1], 'color' => $definition[2],
                'total' => $summary[$key], 'share' => $this->share($summary[$key], $total)];
        }

        $issues = [];
        foreach (['level' => $levels, 'period' => $periods] as $type => $categories) {
            foreach (['__missing' => 'Missing '.$type, '__multiple' => 'Multiple '.$type.' tags'] as $key => $label) {
                $match = collect($categories)->firstWhere('key', $key);
                $issues[] = ['label' => $label, 'total' => $match['total'] ?? 0];
            }
        }
        $issues[] = ['label' => 'Missing composer record', 'total' => $summary['missing_composer']];

        return compact('summary', 'months', 'growth', 'levels', 'periods', 'lengths', 'genders', 'composers', 'media', 'issues') + [
            'countries' => $this->countries($total), 'matrix' => $this->matrix($levels, $periods),
            'favorites' => $this->ranking('favorites'), 'views' => $this->ranking('piece_views'),
        ];
    }

    private function share($count, $total): float
    {
        return $total ? round($count / $total * 100, 1) : 0;
    }

    private function classification(string $type)
    {
        return DB::table('piece_tag as pt')->join('tags as t', 't.id', '=', 'pt.tag_id')
            ->where('t.type', $type)->whereRaw("TRIM(t.name) <> ''")->select('pt.piece_id')
            ->selectRaw('COUNT(DISTINCT LOWER(TRIM(t.name))) as assignments, MIN(LOWER(TRIM(t.name))) as label')
            ->groupBy('pt.piece_id');
    }

    private function category(string $alias): string
    {
        return "CASE WHEN $alias.assignments > 1 THEN '__multiple' WHEN $alias.assignments = 1 THEN $alias.label ELSE '__missing' END";
    }

    private function distribution(string $type, array $order, int $total): array
    {
        $label = $this->category('classification');
        $complete = $type === 'level' ? 'SUM(CASE WHEN '.$this->complete().' THEN 1 ELSE 0 END)' : '0';
        $rows = DB::table('pieces as p')->leftJoinSub($this->classification($type), 'classification', 'classification.piece_id', '=', 'p.id')
            ->selectRaw($label.' as label, COUNT(*) as total, '.$complete.' as complete')
            ->groupByRaw($label)->get();
        return $this->categories($rows, $order, $type, $total);
    }

    private function categories($rows, array $order, string $type, int $total): array
    {
        $indexed = $rows->keyBy('label');
        $extra = array_diff($indexed->keys()->all(), $order, ['__missing', '__multiple']);
        sort($extra);
        $result = [];
        foreach (array_merge($order, $extra, ['__missing', '__multiple']) as $key) {
            $count = (int) ($indexed[$key]->total ?? 0);
            if (in_array($key, ['__missing', '__multiple'], true) && !$count) continue;
            $label = $key === '__missing' ? 'Missing '.$type : ($key === '__multiple' ? 'Multiple '.$type.'s' : ucfirst($key));
            $result[] = ['key' => $key, 'label' => $label, 'total' => $count, 'complete' => (int) ($indexed[$key]->complete ?? 0),
                'share' => $this->share($count, $total), 'color' => $key === '__missing' ? '#c8ccca' : self::COLORS[count($result) % count(self::COLORS)]];
        }
        return $result;
    }

    private function growth(int $months, $now): array
    {
        $start = $now->copy()->startOfMonth()->subMonths($months - 1);
        $running = DB::table('pieces')->where('created_at', '<', $start)->count();
        $rows = DB::table('pieces')->where('created_at', '>=', $start)->where('created_at', '<=', $now)
            ->selectRaw('SUBSTR(created_at, 1, 7) as month, COUNT(*) as total')->groupByRaw('SUBSTR(created_at, 1, 7)')->get()->keyBy('month');
        $trend = [];
        for ($index = 0; $index < $months; $index++) {
            $month = $start->copy()->addMonths($index);
            $added = (int) ($rows[$month->format('Y-m')]->total ?? 0);
            $running += $added;
            $trend[] = ['month' => $month->format('Y-m'), 'label' => $month->format('M Y'), 'short' => $month->format('M'), 'added' => $added, 'total' => $running];
        }
        return $trend;
    }

    private function matrix(array $levels, array $periods): array
    {
        $level = $this->category('l'); $period = $this->category('r');
        $rows = DB::table('pieces as p')->leftJoinSub($this->classification('level'), 'l', 'l.piece_id', '=', 'p.id')
            ->leftJoinSub($this->classification('period'), 'r', 'r.piece_id', '=', 'p.id')
            ->selectRaw($level.' as level, '.$period.' as period, COUNT(*) as total')->groupByRaw($level.', '.$period)->get();
        $cells = [];
        foreach ($rows as $row) $cells[$row->level][$row->period] = (int) $row->total;
        return ['levels' => $levels, 'periods' => $periods, 'cells' => $cells, 'peak' => (int) ($rows->max('total') ?? 0)];
    }

    private function countries(int $total): array
    {
        $world = json_decode(file_get_contents(resource_path('data/admin-world-map.json')), true, 512, JSON_THROW_ON_ERROR);
        $names = json_decode(file_get_contents(resource_path('data/country-continents.json')), true, 512, JSON_THROW_ON_ERROR);
        foreach ($world['countries'] as $shape) $names[$shape['code']]['name'] = $shape['name'];
        $rows = DB::table('pieces as p')->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')->leftJoin('countries as country', 'country.id', '=', 'c.country_id')
            ->selectRaw('UPPER(TRIM(country.flag_code)) as code, COUNT(*) as total')->groupByRaw('UPPER(TRIM(country.flag_code))')->get();
        $countries = [];
        foreach ($rows as $row) {
            if (isset($names[$row->code])) $countries[] = ['code' => $row->code, 'name' => $names[$row->code]['name'], 'total' => (int) $row->total];
        }
        usort($countries, function ($a, $b) { return ($b['total'] <=> $a['total']) ?: strcmp($a['name'], $b['name']); });
        $located = array_sum(array_column($countries, 'total'));
        foreach ($countries as $index => &$country) {
            $country['share'] = $this->share($country['total'], $located);
            $country['color'] = $index < 6 ? (string) $index : 'other';
        }
        unset($country);
        return ['total' => $total, 'located' => $located, 'unlocated' => $total - $located, 'countries' => $countries,
            'byCode' => array_column($countries, null, 'code'), 'top' => $countries[0] ?? null, 'world' => $world];
    }

    private function ranking(string $table): array
    {
        // Only internal callers choose these tables; no submitted model/column names.
        $counts = ExcludedAccounts::owned(DB::table($table), $table.'.user_id')
            ->select('piece_id')->selectRaw('COUNT(*) as total')->groupBy('piece_id');
        return DB::table('pieces as p')->joinSub($counts, 'counts', 'counts.piece_id', '=', 'p.id')
            ->leftJoin('composers as c', 'c.id', '=', 'p.composer_id')->select('p.id', 'p.name', 'p.nickname', 'c.name as composer', 'counts.total')
            ->orderByDesc('counts.total')->orderBy('p.id')->limit(8)->get()->map(function ($row) {
                return ['id' => $row->id, 'label' => $row->nickname ?: $row->name, 'composer' => $row->composer ?? 'Missing composer', 'total' => (int) $row->total];
            })->all();
    }
}
