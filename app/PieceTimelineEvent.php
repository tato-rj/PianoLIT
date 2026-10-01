<?php

namespace App;

class PieceTimelineEvent extends PianoLit
{
    protected $casts = ['year' => 'integer', 'piece_id' => 'integer'];
    protected $fillable = [
        'year', 'event_date', 'title', 'description', 'image_url', 'image_source_url',
        'image_credit', 'image_license', 'image_license_url', 'source_url', 'source_id',
        'wikidata_id', 'event_kind', 'attribution',
    ];

    public function piece()
    {
        return $this->belongsTo(Piece::class);
    }

    public function scopeChronological($query)
    {
        return $query->orderBy('year')->orderBy('event_date')->orderBy('id');
    }
}
