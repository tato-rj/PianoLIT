<?php

namespace App\Console\Commands;

use App\Services\{EmailCampaignReports, MailgunEvents};
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{Http, Schema, Validator};

class RecoverMailgunEvents extends Command
{
    protected $signature = 'email-reports:recover {--from= : First UTC date, YYYY-MM-DD}
        {--to= : Exclusive last UTC date, YYYY-MM-DD} {--campaign=* : Limit to exact local campaign IDs}
        {--max-pages=100 : Maximum provider pages, each containing at most 300 events}
        {--apply : Update matching local tracking records and campaign summaries}';
    protected $description = 'Preview or recover retained Mailgun events without sending email or changing provider data.';

    public function handle(MailgunEvents $events, EmailCampaignReports $reports)
    {
        $input = Validator::make([
            'from' => $this->option('from'), 'to' => $this->option('to'),
            'campaigns' => $this->option('campaign'), 'max_pages' => $this->option('max-pages'),
        ], [
            'from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after:from',
            'campaigns' => 'array', 'campaigns.*' => 'required|string|max:255|not_regex:/[[:cntrl:]]/',
            'max_pages' => 'required|integer|min:1|max:1000',
        ]);
        if ($input->fails()) {
            foreach ($input->errors()->all() as $error) $this->error($error);
            return 1;
        }
        $from = Carbon::createFromFormat('!Y-m-d', $this->option('from'), 'UTC');
        $to = Carbon::createFromFormat('!Y-m-d', $this->option('to'), 'UTC');
        if ($from->diffInDays($to) > 31) {
            $this->error('Recover at most 31 days per run.');
            return 1;
        }
        $host = config('services.mailgun.endpoint', 'api.mailgun.net');
        $domain = config('services.mailgun.domain');
        $key = config('services.mailgun.secret');
        if (! in_array($host, ['api.mailgun.net', 'api.eu.mailgun.net'], true)
            || ! is_string($domain) || ! preg_match('/\A[a-z0-9][a-z0-9.-]*\.[a-z]{2,}\z/i', $domain)
            || ! is_string($key) || $key === '') {
            $this->error('Configure a Mailgun API key, sending domain and US/EU API endpoint on the server.');
            return 1;
        }
        if (! Schema::hasTable('mailgun_event_receipts')) {
            $this->error('Deploy the mailgun_event_receipts migration first.');
            return 1;
        }
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying retained events to local analytics only. No email will be sent.' : 'DRY RUN: no local writes and no provider changes.');
        $this->warn('An empty or partial provider result does not prove zero activity. Retention may have expired.');
        $base = 'https://'.$host.'/v3/'.rawurlencode($domain).'/events';
        $url = $base;
        $parameters = ['begin' => (string) $from->timestamp, 'end' => (string) $to->timestamp, 'ascending' => 'yes', 'limit' => 300];
        $seenPages = $seenEvents = $campaigns = $totals = $projections = [];
        $complete = false;
        try {
            for ($page = 0; $page < (int) $this->option('max-pages'); $page++) {
                $parts = parse_url($url);
                if (($parts['scheme'] ?? null) !== 'https' || ($parts['host'] ?? null) !== $host
                    || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])
                    || (($parts['path'] ?? '') !== parse_url($base, PHP_URL_PATH)
                        && strpos($parts['path'] ?? '', parse_url($base, PHP_URL_PATH).'/') !== 0)
                    || isset($seenPages[$url])) {
                    throw new \RuntimeException('Unsafe or repeated Mailgun pagination URL.');
                }
                $seenPages[$url] = true;
                $response = Http::withBasicAuth('api', $key)->acceptJson()->timeout(30)
                    ->withOptions(['allow_redirects' => false, 'connect_timeout' => 10])->get($url, $parameters);
                if (! $response->successful()) throw new \RuntimeException('Mailgun returned HTTP '.$response->status().'.');
                $items = $response->json('items');
                if (! is_array($items) || count($items) > 300) throw new \RuntimeException('Invalid Mailgun event page.');
                foreach ($items as $payload) {
                    try {
                        if (! is_array($payload)) throw new \InvalidArgumentException('Invalid event.');
                        $event = $events->normalize($payload);
                        if ($event === null) { $totals['ignored'] = ($totals['ignored'] ?? 0) + 1; continue; }
                        if ($event['occurred_at']->lt($from) || $event['occurred_at']->gte($to)) continue;
                        if (isset($seenEvents[$event['event_key']])) continue;
                        $seenEvents[$event['event_key']] = true;
                        $result = $events->apply($event, ! $apply, true, null, $this->option('campaign'));
                    } catch (\Illuminate\Validation\ValidationException | \InvalidArgumentException $exception) {
                        $totals['invalid'] = ($totals['invalid'] ?? 0) + 1;
                        continue;
                    }
                    $totals[$result['status']] = ($totals[$result['status']] ?? 0) + 1;
                    if (! empty($result['campaign'])) $campaigns[$result['campaign']] = true;
                    if (! $apply && ! empty($result['campaign'])) {
                        foreach ($result['new_flags'] ?? [] as $flag) $projections[$result['campaign']][$result['email_log_id']][$flag] = true;
                    }
                }
                $next = $response->json('paging.next');
                if (! $items || $next === null || $next === '') { $complete = true; break; }
                if (! is_string($next)) throw new \RuntimeException('Invalid Mailgun pagination URL.');
                $url = $next;
                $parameters = [];
            }
        } catch (\Throwable $exception) {
            // Do not print provider bodies, recipient addresses or authentication options.
            $this->error('Recovery stopped before completion. Check Mailgun access/configuration and retry; applied events are safe to replay.');
            return 1;
        }
        $rows = [];
        $columns = ['delivered' => 'delivered_count', 'failed' => 'failed_count', 'opened' => 'opens_count', 'clicked' => 'clicks_count'];
        foreach (array_keys($campaigns) as $campaign) {
            $counts = $reports->rebuild($campaign, ! $apply);
            foreach ($projections[$campaign] ?? [] as $flags) {
                foreach ($flags as $flag => $enabled) $counts[$columns[$flag]]++;
            }
            if ($counts) $rows[] = [$campaign, $counts['emails_count'], $counts['delivered_count'], $counts['failed_count'], $counts['opens_count'], $counts['clicks_count']];
        }
        if ($rows) $this->info($apply ? 'Recovered unique campaign counts:' : 'Projected unique campaign counts:');
        if ($rows) $this->table(['Campaign', 'Logged emails', 'Delivered', 'Failed', 'Opened', 'Clicked'], $rows);
        foreach ($totals as $status => $count) $this->line(ucfirst($status).' events: '.$count);
        if (! $complete || ! empty($totals['invalid'])) {
            $this->warn('This run is incomplete. Increase --max-pages or investigate invalid events before treating it as a full recovery.');
            return 1;
        }
        $this->info($apply ? 'Recovery completed for retained, exactly matched events.' : 'Preview complete. Add --apply to recover matching events and rebuild only their summaries.');
        return 0;
    }
}
