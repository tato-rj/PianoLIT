<?php

namespace App;

class TimelineEvent extends PianoLit
{
    protected $casts = ['year' => 'integer'];
    protected $fillable = [
        'year', 'event_date', 'title', 'description', 'image_url', 'image_source_url',
        'image_credit', 'image_license', 'image_license_url', 'source_url', 'source_id',
        'wikidata_id', 'event_kind', 'attribution',
    ];

    protected static function booted()
    {
        static::saving(function ($event) {
            // Match discovery identity even if the curated year or upstream date changes.
            $kind = in_array($event->event_kind, ['birth', 'death']) ? $event->event_kind : 'context';
            $event->source_identity = $event->wikidata_id.':'.$kind;
        });
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('year')->orderBy('event_date')->orderBy('id');
    }
}
