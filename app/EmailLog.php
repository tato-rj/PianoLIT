<?php

namespace App;

use Illuminate\Support\Facades\DB;

class EmailLog extends PianoLit
{
    protected static function boot()
    {
        parent::boot();

        static::created(function ($log) {
            if (! $log->list_id) {
                return;
            }

            DB::table('email_campaign_reports')->insertOrIgnore([
                'list_id' => $log->list_id,
                'name' => $log->name,
                'sent_at' => $log->sent_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            EmailCampaignReport::where('list_id', $log->list_id)->update([
                'emails_count' => DB::raw('emails_count + 1'),
                'delivered_count' => DB::raw('delivered_count + '.(int) $log->unique_delivered),
                'failed_count' => DB::raw('failed_count + '.(int) $log->unique_failed),
                'opens_count' => DB::raw('opens_count + '.(int) $log->unique_opened),
                'clicks_count' => DB::raw('clicks_count + '.(int) $log->unique_clicked),
                'updated_at' => now(),
            ]);
        });

        static::updated(function ($log) {
            if (! $log->list_id) {
                return;
            }

            $columns = [
                'unique_delivered' => 'delivered_count',
                'unique_failed' => 'failed_count',
                'unique_opened' => 'opens_count',
                'unique_clicked' => 'clicks_count',
            ];

            foreach ($columns as $logColumn => $reportColumn) {
                if ($log->wasChanged($logColumn)) {
                    $difference = (int) $log->{$logColumn} - (int) $log->getOriginal($logColumn);
                    EmailCampaignReport::where('list_id', $log->list_id)->increment($reportColumn, $difference);
                }
            }
        });
    }

    protected $dates = [
        'delivered_at',
        'failed_at',
    ];

    protected $appends = ['name', 'sent_at'];

    public function sender()
    {
        return $this->morphTo();
    }

    public function getNameAttribute()
    {
    	if (! $this->list_id)
    		return null;

    	return slug_str($this->idTo('name'));
    }

    public function getSentAtAttribute()
    {
    	if (! $this->list_id)
    		return null;

    	return now()->createFromTimestamp($this->idTo('date'));
    }

    public function idTo($option)
    {
    	$key = array_search($option, ['name', 'date']);

    	return explode('.', $this->list_id)[$key];
    }

    public function scopeGenerate($query, $list = null)
    {
    	$report = $list ? $query->where('list_id', $list) : $query->whereNotNull('list_id');

        return $report->selectRaw('list_id, 
                                  count(*) emails_count, 
                                  sum(unique_delivered) delivered_count, 
                                  sum(unique_failed) failed_count, 
                                  sum(unique_opened) opens_count, 
                                  sum(unique_clicked) clicks_count')
                      ->groupBy('list_id');
    }

    public function scopeByList($query, $listId)
    {
        return $query->where('list_id', $listId);
    }

    public function scopeByLists($query, array $ids)
    {
        return $query->whereIn('list_id', $ids);
    }

    public function scopeDatatable($query, $list)
    {
        return datatable($query->byList($list))->withBlade([
            'status' => view('admin.pages.reports.show.table.status')
        ])->withTime(['delivered_at', 'failed_at'])->make();
    }
}
