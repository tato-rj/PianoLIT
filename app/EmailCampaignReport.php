<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailCampaignReport extends Model
{
    protected $guarded = [];
    protected $dates = ['sent_at'];

    protected $casts = [
        'emails_count' => 'integer',
        'delivered_count' => 'integer',
        'failed_count' => 'integer',
        'opens_count' => 'integer',
        'clicks_count' => 'integer',
    ];
}
