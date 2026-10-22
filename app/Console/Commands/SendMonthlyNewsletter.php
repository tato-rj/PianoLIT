<?php

namespace App\Console\Commands;

use App\EmailList;
use App\Services\Newsletter\MonthlyDigest;
use Illuminate\Console\Command;

class SendMonthlyNewsletter extends Command
{
    protected $signature = 'newsletter:send-monthly {--execute : Queue the digest; otherwise preview without writes}';
    protected $description = 'Queue last month’s newly published PianoLIT hub content once, skipping empty months';

    public function handle(MonthlyDigest $digests)
    {
        if (!$this->option('execute')) {
            $list = EmailList::where('name', EmailList::NEWSLETTER)->first();
            $items = $digests->items($digests->month());
            $this->info('Preview: '.$digests->month().'; '.$items->count().' new items; '.($list ? $list->subscribers()->count() : 0).' newsletter subscribers.');
            foreach ($items as $item) $this->line($item->hub.' · '.$item->type.' · '.$item->title.' · '.$item->url);
            return 0;
        }
        $campaign = $digests->queuePreviousMonth();
        $digests->pruneDeliveries();
        $this->info($campaign ? 'Queued '.$campaign.'.' : 'No newsletter queued: no new content, no subscribers, or this month was already queued.');
        return 0;
    }
}
