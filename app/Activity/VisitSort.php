<?php

namespace App\Activity;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitSort
{
    const OPTIONS = [
        'date' => 'Date/time', 'duration' => 'Duration', 'pages' => 'Pages', 'actions' => 'Actions',
    ];
    const VALUES = [
        'date' => ['desc' => 'newest', 'asc' => 'oldest'],
        'duration' => ['desc' => 'longest', 'asc' => 'shortest'],
        'pages' => ['desc' => 'most_pages', 'asc' => 'fewest_pages'],
        'actions' => ['desc' => 'most_actions', 'asc' => 'fewest_actions'],
    ];

    public $value;
    public $field;
    public $order;

    public function __construct(Request $request = null)
    {
        // Existing bookmarked combined choices continue to resolve correctly.
        $allowed = array_keys(self::OPTIONS);
        foreach (self::VALUES as $orders) $allowed = array_merge($allowed, array_values($orders));
        $input = $request ? $request->validate([
            'visit_sort' => 'nullable|string|in:'.implode(',', $allowed),
            'visit_order' => 'nullable|string|in:asc,desc',
        ]) : [];
        $requested = $input['visit_sort'] ?? 'date';
        foreach (self::VALUES as $field => $orders) {
            $legacyOrder = array_search($requested, $orders, true);
            if ($requested === $field || $legacyOrder !== false) {
                $this->field = $field;
                $this->order = $input['visit_order'] ?? ($legacyOrder !== false ? $legacyOrder : 'desc');
                break;
            }
        }
        $this->value = self::VALUES[$this->field][$this->order];
    }

    public function reverseOrder()
    {
        return $this->order === 'asc' ? 'desc' : 'asc';
    }

    public function orderLabel($order = null)
    {
        $labels = [
            'date' => ['desc' => 'Newest first', 'asc' => 'Oldest first'],
            'duration' => ['desc' => 'Longest first', 'asc' => 'Shortest first'],
            'pages' => ['desc' => 'Highest first', 'asc' => 'Lowest first'],
            'actions' => ['desc' => 'Highest first', 'asc' => 'Lowest first'],
        ];
        return $labels[$this->field][$order ?: $this->order];
    }

    public function byDate()
    {
        return in_array($this->value, ['newest', 'oldest'], true);
    }

    public function byActions()
    {
        return in_array($this->value, ['most_actions', 'fewest_actions'], true);
    }

    public function description()
    {
        return [
            'newest' => 'Newest visits first.', 'oldest' => 'Oldest visits first.',
            'longest' => 'Longest visits first.', 'shortest' => 'Shortest visits first.',
            'most_pages' => 'Visits with the most pages first.', 'fewest_pages' => 'Visits with the fewest pages first.',
            'most_actions' => 'Visits with the most actions first.', 'fewest_actions' => 'Visits with the fewest actions first.',
        ][$this->value];
    }

    public function apply($query, $candidates)
    {
        $direction = in_array($this->value, ['oldest', 'shortest', 'fewest_pages', 'fewest_actions'], true) ? 'asc' : 'desc';
        if ($this->byDate()) return $query->orderBy('v.started_at', $direction)->orderBy('v.id', $direction);
        if (in_array($this->value, ['longest', 'shortest'], true)) {
            $span = $this->durationExpression();
            $query->selectRaw("CASE WHEN ($span) > 0 THEN ($span) ELSE NULL END as visit_duration_ms")
                ->orderByRaw('visit_duration_ms IS NULL')->orderBy('visit_duration_ms', $direction);
        } elseif ($this->byActions()) {
            $query->leftJoinSub($this->actionTotals($candidates), 'visit_actions', 'visit_actions.visit_id', '=', 'v.id')
                ->selectRaw('(CASE WHEN COALESCE(visit_actions.has_piece, 0) = 1 OR EXISTS (SELECT 1 FROM activity_pages AS ap WHERE ap.visit_id = v.id) THEN 1 ELSE 0 END) + COALESCE(visit_actions.tags, 0) as actions_count')
                ->orderBy('actions_count', $direction);
        } else {
            $query->orderBy('pages_count', $direction);
        }
        return $query->orderByDesc('v.started_at')->orderByDesc('v.id');
    }

    private function durationExpression()
    {
        $difference = function ($start, $last) {
            if (DB::getDriverName() === 'sqlite') return "(strftime('%s', $last) - strftime('%s', $start)) * 1000 + CAST(SUBSTR($last, 21, 3) AS INTEGER) - CAST(SUBSTR($start, 21, 3) AS INTEGER)";
            return "TIMESTAMPDIFF(MICROSECOND, $start, $last) / 1000";
        };
        // Canonical pairs retain elapsed time through daylight-saving changes.
        // Older visits use their existing local timestamp pair, as the pruner does.
        return 'CASE WHEN v.started_at_utc IS NOT NULL AND v.last_activity_at_utc IS NOT NULL THEN ('.$difference('v.started_at_utc', 'v.last_activity_at_utc').') ELSE ('.$difference('v.started_at', 'v.last_activity_at').') END';
    }

    private function actionTotals($candidates)
    {
        // Count the distinct tags rendered by Queries::summaryParts(), not rows
        // or the piece number inside a tag. Keep new summary types in sync here.
        $grammar = DB::connection()->getQueryGrammar();
        $kind = $grammar->wrap('ae.metadata->media_kind');
        $folder = $grammar->wrap('ae.metadata->folder_id');
        $result = $grammar->wrap('ae.metadata->result');
        $textResult = 'CAST('.$result.' AS '.(DB::getDriverName() === 'sqlite' ? 'TEXT' : 'CHAR').')';
        $isFolder = "($folder IS NOT NULL AND $folder <> 'null')";
        $favorite = "ae.type = 'favorite_changed' AND ae.source = 'server'";
        $conditions = [
            "ae.type = 'media_play' AND $kind = 'audio'",
            "ae.type = 'media_play' AND $kind = 'video'",
            "ae.type = 'media_play' AND ($kind IS NULL OR $kind NOT IN ('audio', 'video'))",
            "$favorite AND $isFolder",
            "$favorite AND NOT $isFolder AND $textResult IN ('true', '1')",
            "$favorite AND NOT $isFolder AND $result IS NOT NULL AND $textResult NOT IN ('true', '1')",
            "$favorite AND NOT $isFolder AND $result IS NULL",
        ];
        foreach (['score_opened', 'annotation_saved', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'] as $type) $conditions[] = "ae.type = '$type'";
        $tags = implode(' + ', array_map(function ($condition) { return "MAX(CASE WHEN $condition THEN 1 ELSE 0 END)"; }, $conditions));
        return ExcludedAccounts::events(DB::table('activity_events as ae'), 'ae')->whereIn('ae.visit_id', $candidates)
            ->whereIn('ae.type', ['page_view', 'media_play', 'score_opened', 'annotation_saved', 'favorite_changed', 'folder_created', 'trial_started', 'subscription_paid', 'premium_opened'])
            ->select('ae.visit_id')->selectRaw("$tags as tags, MAX(CASE WHEN ae.type = 'page_view' AND ae.source = 'server' AND ae.resource_type = 'piece' AND ae.resource_id IS NOT NULL THEN 1 ELSE 0 END) as has_piece")
            ->groupBy('ae.visit_id');
    }
}
