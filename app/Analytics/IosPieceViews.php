<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class IosPieceViews
{
    // The legacy counter's last stored event was at 14:55:25 (app local time).
    private const LOGS_FROM = '2023-06-07 14:55:26';

    public function counts(): Builder
    {
        $saved = ExcludedAccounts::owned(DB::table('piece_views'))
            ->where(fn ($query) => $query->where('created_at', '<', self::LOGS_FROM)->orWhereNull('created_at'))
            ->select('piece_id')->selectRaw('COUNT(*) as total')->groupBy('piece_id');

        $confirmed = ExcludedAccounts::owned(DB::table('activity_events'))
            ->where('platform', 'app')->where('source', 'server')->where('type', 'piece_opened')
            ->where('resource_type', 'piece')->whereNotNull('user_id')->whereNotNull('resource_id')
            ->selectRaw('CAST(resource_id AS UNSIGNED) as piece_id, COUNT(*) as total')->groupBy('resource_id');

        $sources = $saved->unionAll($this->legacyRequests())->unionAll($confirmed);

        return DB::query()->fromSub($sources, 'ios_sources')
            ->select('piece_id')->selectRaw('SUM(total) as total')->groupBy('piece_id');
    }

    private function legacyRequests(): Builder
    {
        $path = '/api/pieces/find';
        $host = config('app.short_url');
        $urls = [$path, 'https://'.$host.$path, 'http://'.$host.$path];
        $search = DB::connection()->getQueryGrammar()->wrap('e.metadata->data->search');
        $searchType = DB::connection()->getDriverName() === 'sqlite'
            ? "JSON_TYPE(e.metadata, '$.data.search')"
            : "LOWER(JSON_TYPE(JSON_EXTRACT(e.metadata, '$.data.search')))";

        return ExcludedAccounts::owned(DB::table('activity_events as e'), 'e.user_id')
            ->where('e.platform', 'app')->whereIn('e.type', ['request', 'legacy_request'])
            ->where('e.occurred_at', '>=', self::LOGS_FROM)->whereNull('e.visit_id')
            ->whereNotIn('e.user_id', [196, 244])
            ->where(fn ($query) => $query->whereNull('e.method')->orWhereIn('e.method', ['GET', 'POST']))
            ->where(function ($query) use ($urls) {
                $query->whereIn('e.url', $urls);
                foreach ($urls as $url) $query->orWhere('e.url', 'like', $url.'?%');
            })
            ->whereIn(DB::raw($searchType), ['integer', 'string', 'text'])
            ->join('pieces as p', function ($join) use ($search) {
                // Keep the primary-key lookup and reject coerced IDs like "12x" or "12 ".
                $join->whereRaw("p.id = CAST($search AS UNSIGNED)")
                    ->whereRaw("CAST(p.id AS CHAR) = CAST($search AS CHAR)")
                    ->whereRaw("LENGTH(CAST(p.id AS CHAR)) = LENGTH(CAST($search AS CHAR))");
            })
            ->selectRaw('p.id as piece_id, COUNT(*) as total')->groupBy('p.id');
    }
}
