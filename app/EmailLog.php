<?php

namespace App;

class EmailLog extends PianoLit
{
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

    public function scopeGenerate($query)
    {
    	return $query->whereNotNull('list_id')
    				 ->selectRaw('list_id, count(*) emails_count, sum(opened) opens_count, sum(clicked) clicks_count')
    				 ->groupBy('list_id')
    				 ->orderBy('list_id', 'DESC');
    }

    public function scopeByList($query, $listId)
    {
        return $query->where('list_id', $listId);
    }
}
