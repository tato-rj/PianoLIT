<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Illuminate\Support\Facades\{DB, Redis};
use Illuminate\Support\Str;

class ImportRedisActivity extends Command
{
    protected $signature = 'activity:import-redis
        {--connection=default : Configured Redis connection; a restored snapshot is recommended}
        {--prefix= : Override the legacy application key prefix}
        {--platforms=web,webapp,app : Comma-separated platforms to copy}
        {--before= : Exclusive Unix timestamp cutoff; defaults to the start of this run}
        {--batch=500 : Redis scan COUNT hint (database writes use at most 100 rows)}
        {--sleep-ms=20 : Pause between batches to limit load}
        {--max-batches=0 : Stop after this many batches; zero runs to completion}
        {--verify : After completion, compare each source record with its preserved SQL payload}
        {--restart : Rescan from the beginning, retaining previously imported records}';

    private $excludedIds = [];

    protected $description = 'Copy legacy activity and daily counter snapshots to SQL without changing Redis. Safe to resume or rerun.';

    public function handle()
    {
        $this->excludedIds = array_map('intval', \App\Activity\ExcludedAccounts::ids()->pluck('id')->all());
        $connection = $this->option('connection');
        $prefix = (string) ($this->option('prefix') ?? config('database.redis.prefix', ''));
        $platforms = array_unique(explode(',', $this->option('platforms'))); sort($platforms);
        $batch = filter_var($this->option('batch'), FILTER_VALIDATE_INT);
        $sleep = filter_var($this->option('sleep-ms'), FILTER_VALIDATE_INT);
        $maximum = filter_var($this->option('max-batches'), FILTER_VALIDATE_INT);
        if (!isset(config('database.redis')[$connection]) || !$platforms || array_diff($platforms, ['app', 'web', 'webapp']) || $batch === false || $sleep === false || $maximum === false || $batch < 1 || $batch > 5000 || $sleep < 0 || $sleep > 1000 || $maximum < 0) {
            $this->error('Invalid connection, platforms, batch, sleep-ms, or max-batches option.'); return 1;
        }
        $platformString = implode(',', $platforms);
        $before = $this->option('before');
        if ($before !== null && (!ctype_digit((string) $before) || (int) $before < 1 || (int) $before > time())) {
            $this->error('before must be a positive Unix timestamp no later than now.'); return 1;
        }
        $unfinished = DB::table('activity_imports')->where(compact('connection', 'prefix'))->where('platforms', $platformString)->where('complete', false)->orderByDesc('before')->first();
        $before = (int) ($before ?? ($unfinished ? $unfinished->before : time()));
        $id = hash('sha256', json_encode([$connection, $prefix, $platformString, $before]));
        DB::table('activity_imports')->insertOrIgnore([
            'id' => $id, 'connection' => $connection, 'prefix' => $prefix, 'platforms' => $platformString,
            'before' => $before, 'updated_at' => now(),
        ]);
        if ($this->option('restart')) DB::table('activity_imports')->where('id', $id)->update([
            'cursor' => '0', 'scan_started' => false, 'pending_keys' => '[]', 'current_key' => null,
            'hash_cursor' => '0', 'complete' => false, 'updated_at' => now(),
        ]);
        $redis = Redis::connection($connection);
        $steps = 0;
        $this->info('Import '.$id.'; exclusive cutoff '.$before.'. Redis will not be modified.');
        do {
            // A row lock serializes concurrent workers for this import. Checkpoints
            // and their copied records commit together, so interruption loses no batch.
            $complete = DB::transaction(function () use ($redis, $id, $prefix, $platforms, $batch, $connection, $before) {
                $state = DB::table('activity_imports')->where('id', $id)->lockForUpdate()->first();
                if ($state->complete) return true;
                $pending = json_decode($state->pending_keys ?: '[]', true);
                if ($state->current_key) {
                    [$cursor, $fields] = $this->scan($redis, 'hscan', $state->hash_cursor, $state->current_key, $batch);
                    $rows = [];
                    preg_match('/^'.preg_quote($prefix, '/').'user:(\d+):(app|web|webapp)$/', $state->current_key, $matches);
                    foreach ($fields as $field => $payload) {
                        if (ctype_digit((string) $field) && (float) $field >= $before) continue;
                        if (in_array((int) $matches[1], $this->excludedIds, true)) continue;
                        $rows[] = $this->legacyRow($connection, $state->current_key, (string) $field, (string) $payload, $matches);
                        if (count($rows) === 100) { $this->writeRows($rows); $rows = []; }
                    }
                    if ($rows) $this->writeRows($rows);
                    $state->hash_cursor = (string) $cursor;
                    if ((string) $cursor === '0') { $state->current_key = null; $state->hash_cursor = '0'; }
                } elseif ($pending) {
                    $key = array_shift($pending);
                    if (preg_match('/^'.preg_quote($prefix, '/').'user:(\d+):(app|web|webapp)$/', $key, $matches) && in_array($matches[2], $platforms, true)) {
                        if (in_array((int) $matches[1], $this->excludedIds, true)) {
                            $state->current_key = null;
                        } else {
                            $type = (string) $redis->type($key);
                            if (in_array($type, ['hash', '5'], true)) $state->current_key = $key;
                            else $this->warn('A legacy activity key was missing or not a hash; use a stable snapshot and rescan to verify completeness.');
                        }
                    } elseif (preg_match('/^'.preg_quote($prefix, '/').'logs:daily:(\d{4}-\d{2}-\d{2}):(app|web|webapp)$/', $key, $matches) && in_array($matches[2], $platforms, true)) {
                        $total = $redis->get($key);
                        [$year, $month, $day] = array_map('intval', explode('-', $matches[1]));
                        if (ctype_digit((string) $total) && checkdate($month, $day, $year)) {
                            DB::table('activity_legacy_daily')->upsert([[
                                'id' => hash('sha256', $key), 'date' => $matches[1], 'platform' => $matches[2],
                                'total' => $total, 'source_key' => $key, 'captured_at' => now(),
                            ]], ['date', 'platform'], ['total', 'source_key', 'captured_at']);
                        } else $this->warn('An invalid legacy daily counter was not imported.');
                    }
                } elseif ($state->scan_started && (string) $state->cursor === '0') {
                    $state->complete = true;
                } else {
                    [$cursor, $pending] = $this->scan($redis, 'scan', $state->cursor, $prefix.'*', $batch);
                    $pending = array_values(array_filter($pending, function ($key) use ($prefix) {
                        return preg_match('/^'.preg_quote($prefix, '/').'(user:\d+:(app|web|webapp)|logs:daily:\d{4}-\d{2}-\d{2}:(app|web|webapp))$/', $key);
                    }));
                    $state->cursor = (string) $cursor; $state->scan_started = true;
                }
                DB::table('activity_imports')->where('id', $id)->update([
                    'cursor' => $state->cursor, 'scan_started' => $state->scan_started, 'pending_keys' => json_encode($pending),
                    'current_key' => $state->current_key, 'hash_cursor' => $state->hash_cursor,
                    'complete' => $state->complete, 'updated_at' => now(),
                ]);
                return (bool) $state->complete;
            });
            $steps++;
            if ($steps % 100 === 0) $this->line('Committed '.$steps.' batches in this run.');
            if (!$complete && $sleep) usleep($sleep * 1000);
        } while (!$complete && (!$maximum || $steps < $maximum));
        $this->info($complete ? 'Scan complete. Verify source record counts and payloads before retiring legacy history.' : 'Checkpoint saved. Run the same command again to resume.');
        $this->line('Historical event rows in SQL: '.DB::table('activity_events')->where('source', 'redis')->count());
        if ($complete && $this->option('verify')) return $this->verifyRecords($redis, $prefix, $platforms, $before, $batch, $sleep);
        return 0;
    }

    private function scan($redis, $command, $cursor, $key, $count)
    {
        if ($redis instanceof PhpRedisConnection) {
            $result = $command === 'scan' ? $redis->scan((int) $cursor, ['match' => $key, 'count' => $count]) : $redis->hscan($key, (int) $cursor, ['count' => $count]);
        } else {
            $result = $command === 'scan' ? $redis->scan($cursor, 'MATCH', $key, 'COUNT', $count) : $redis->hscan($key, $cursor, 'COUNT', $count);
        }
        return $result ?: ['0', []];
    }

    private function legacyRow($connection, $key, $field, $payload, $matches)
    {
        $data = json_decode($payload, true);
        $valid = is_array($data) && isset($data['url']) && is_string($data['url']);
        $timestamp = ctype_digit($field) && (float) $field <= 32503680000 ? Carbon::createFromTimestamp((int) $field, config('app.timezone')) : null;
        $encoding = mb_check_encoding($payload, 'UTF-8') ? 'utf-8' : 'base64';
        $fieldEncoding = mb_check_encoding($field, 'UTF-8') ? 'utf-8' : 'base64';
        $metadata = ['legacy' => true, 'visit_unknown' => true, 'payload_encoding' => $encoding, 'field_encoding' => $fieldEncoding];
        if ($valid) $metadata['data'] = $data['data'] ?? null;
        return [
            'event_id' => (string) Str::uuid(), 'user_id' => (int) $matches[1], 'platform' => $matches[2],
            'type' => $valid && $timestamp ? 'legacy_request' : 'legacy_unparsed', 'source' => 'redis',
            'occurred_at' => $timestamp, 'received_at' => now(), 'url' => $valid ? $data['url'] : null,
            'occurred_at_utc' => $timestamp ? $timestamp->copy()->utc()->format('Y-m-d H:i:s.v') : null, 'received_at_utc' => now('UTC')->format('Y-m-d H:i:s.v'),
            'metadata' => json_encode($metadata, JSON_INVALID_UTF8_SUBSTITUTE),
            'legacy_identity' => $this->identity($key, $field),
            'legacy_key' => $key, 'legacy_field' => $fieldEncoding === 'base64' ? base64_encode($field) : $field,
            'legacy_payload' => $encoding === 'base64' ? base64_encode($payload) : $payload,
        ];
    }

    private function writeRows(array $rows)
    {
        DB::table('activity_events')->upsert($rows, ['legacy_identity'], ['user_id', 'platform', 'type', 'occurred_at', 'occurred_at_utc', 'url', 'metadata', 'legacy_payload']);
    }

    private function identity($key, $field)
    {
        return hash('sha256', strlen($key).':'.$key.$field);
    }

    private function verifyRecords($redis, $prefix, $platforms, $before, $batch, $sleep)
    {
        $cursor = '0'; $checked = 0; $mismatches = 0;
        do {
            [$cursor, $keys] = $this->scan($redis, 'scan', $cursor, $prefix.'user:*', $batch);
            foreach ($keys as $key) {
                if (!preg_match('/^'.preg_quote($prefix, '/').'user:(\d+):(app|web|webapp)$/', $key, $matches) || !in_array($matches[2], $platforms, true)) continue;
                if (in_array((int) $matches[1], $this->excludedIds, true)) continue;
                $hashCursor = '0';
                do {
                    [$hashCursor, $fields] = $this->scan($redis, 'hscan', $hashCursor, $key, $batch);
                    foreach (array_chunk($fields, 100, true) as $chunk) {
                        $expected = [];
                        foreach ($chunk as $field => $payload) {
                            if (ctype_digit((string) $field) && (float) $field >= $before) continue;
                            $expected[$this->identity($key, (string) $field)] = (string) $payload;
                        }
                        $stored = DB::table('activity_events')->whereIn('legacy_identity', array_keys($expected))->get(['legacy_identity', 'legacy_payload', 'metadata'])->keyBy('legacy_identity');
                        foreach ($expected as $identity => $payload) {
                            $row = $stored->get($identity);
                            $raw = $row ? $row->legacy_payload : null;
                            if ($row && (json_decode($row->metadata, true)['payload_encoding'] ?? '') === 'base64') $raw = base64_decode($raw, true);
                            $checked++;
                            if ($raw !== $payload) $mismatches++;
                        }
                    }
                    if ($sleep) usleep($sleep * 1000);
                } while ((string) $hashCursor !== '0');
            }
        } while ((string) $cursor !== '0');
        $this->info('Verification: '.$checked.' source record checks; '.$mismatches.' missing or different payloads. SCAN may revisit a record.');
        if ($mismatches) $this->error('Keep Redis. Rescan with --restart against the same stable source before retiring history.');
        return $mismatches ? 1 : 0;
    }
}
