<?php

namespace App\Services;

use App\{EmailList, FreePick};
use App\Jobs\SendFreePickCampaign;
use Illuminate\Support\Facades\DB;

class FreePickRelease
{
    public function processDue()
    {
        $count = 0;
        FreePick::where('refresh_discover', true)->whereNull('release_completed_at')
            ->whereNull('retired_at')->whereNotNull('activated_at')->where('scheduled_at', '<=', now('UTC'))
            ->chunkById(100, function ($picks) use (&$count) {
                foreach ($picks as $pick) {
                    try {
                        if ($this->release($pick->id)) $count++;
                    } catch (\Throwable $exception) {
                        // Leave the durable checkpoints pending for the next scheduler tick.
                        report($exception);
                    }
                }
            });
        return $count;
    }

    public function release($id)
    {
        // A committed refresh checkpoint survives a later queue outage.
        DB::transaction(function () use ($id) {
            $pick = FreePick::whereKey($id)->lockForUpdate()->first();
            if (!$this->pending($pick)) return;
            if ((int) FreePick::current()->value('id') !== $pick->id) {
                // Do not announce superseded picks after scheduler downtime.
                $pick->update(['release_completed_at' => now('UTC')]);
                return;
            }
            if (!$pick->discover_refreshed_at) {
                app(DiscoverRefresh::class)->refresh();
                $pick->update(['discover_refreshed_at' => now('UTC')]);
            }
        });

        return DB::transaction(function () use ($id) {
            $pick = FreePick::whereKey($id)->lockForUpdate()->first();
            if (!$this->pending($pick) || !$pick->discover_refreshed_at) return false;
            if ((int) FreePick::current()->value('id') !== $pick->id) {
                $pick->update(['release_completed_at' => now('UTC')]);
                return false;
            }
            if ($pick->send_email && !$pick->email_queued_at) {
                $list = EmailList::freepick();
                if (!$list) throw new \RuntimeException('Create the Free Pick email list before sending its scheduled campaign.');
                // A repeated fan-out after an ambiguous queue failure is harmless:
                // recipients are serialized by their unique delivery record.
                SendFreePickCampaign::dispatch($pick->id)->onConnection('redis');
                $pick->email_queued_at = now('UTC');
                $list->update(['last_sent_at' => now()]);
            }
            $pick->release_completed_at = now('UTC');
            $pick->save();
            return true;
        });
    }

    private function pending($pick)
    {
        return $pick && $pick->refresh_discover && !$pick->release_completed_at && !$pick->retired_at
            && $pick->activated_at && $pick->scheduled_at->lessThanOrEqualTo(now('UTC'));
    }
}
