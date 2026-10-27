<?php

namespace App\Activity;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Support\AdminDateRanges;

class Filters
{
    const PLATFORMS = ['webapp' => 'Web app', 'app' => 'iOS'];

    public $start;
    public $end;
    public $values;
    public $platforms;

    public function __construct(Request $request)
    {
        $this->values = $request->validate([
            'range' => 'nullable|string|in:'.implode(',', array_merge(array_keys(AdminDateRanges::LABELS), ['30'])),
            'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from',
            'platform' => is_array($request->input('platform')) ? 'array|min:1|max:4' : 'nullable|string|in:all,web,webapp,app',
            'platform.*' => 'nullable|string|in:web,webapp,app|distinct',
            'audience' => 'nullable|in:all,registered,anonymous',
        ]);
        $this->values = array_merge(['range' => '7', 'platform' => 'all', 'audience' => 'registered'], array_filter($this->values, function ($value) { return $value !== null; }));
        // Accept existing scalar URLs and the checkbox menu's array (including
        // its empty sentinel so an unchecked menu cannot silently mean All).
        $selected = $this->values['platform'];
        // Saved Website links now open the app-focused report. Historical rows
        // are retained; public traffic belongs in Google Analytics.
        if ($selected === 'web') $selected = $this->values['platform'] = 'webapp';
        $this->platforms = $selected === 'all' ? array_keys(self::PLATFORMS) : array_values(array_intersect(array_keys(self::PLATFORMS), (array) $selected));
        if (!$this->platforms) throw ValidationException::withMessages(['platform' => 'Choose at least one platform.']);
        if (is_array($selected)) $this->values['platform'] = $this->platforms;
        $today = now()->startOfDay();
        // Preserve saved Last 30 days links as a custom range without offering
        // the retired preset in either date selector.
        if ($this->values['range'] === '30') {
            [$from, $to] = AdminDateRanges::bounds('30', $today);
            $this->values = array_merge($this->values, ['range' => 'custom', 'from' => $from->toDateString(), 'to' => $to->toDateString()]);
        }
        switch ($this->values['range']) {
            case 'custom':
                if (empty($this->values['from']) || empty($this->values['to'])) throw ValidationException::withMessages(['from' => 'Choose both dates for a custom period.']);
                $this->start = Carbon::parse($this->values['from'])->startOfDay();
                $this->end = Carbon::parse($this->values['to'])->addDay()->startOfDay();
                break;
            default:
                [$this->start, $last] = AdminDateRanges::bounds($this->values['range'], $today);
                $this->end = $last->copy()->addDay()->startOfDay();
                if ($this->values['range'] === 'all') unset($this->values['from'], $this->values['to']);
        }
    }

    public function apply($query, $column, $platform = 'platform')
    {
        if ($this->start) $query->where($column, '>=', $this->start);
        $query->where($column, '<', $this->end);
        $query->whereIn($platform, $this->platforms);
        return $query;
    }

    public function includesPlatform($platform)
    {
        return in_array($platform, $this->platforms, true);
    }
}
