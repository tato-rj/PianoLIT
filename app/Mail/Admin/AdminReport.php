<?php

namespace App\Mail\Admin;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Mail\Traits\EditorialLayout;

class AdminReport extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, EditorialLayout;

    public $theme = 'welcome';

    public $reports, $recipient;

    public function __construct($reports, $recipient)
    {
        $this->reports = $reports;
        $this->recipient = $recipient;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // A queued report from before this release still contains the old records.
        if (!isset($this->reports['period'])) {
            $this->theme = 'default';
            return $this->subject('PianoLIT weekly report')->markdown('emails.admin.report.legacy');
        }
        return $this->subject('PianoLIT weekly snapshot · '.$this->reports['period']['label'])
            ->markdown('emails.admin.report.index', [
                'snapshot' => $this->reports,
                'websiteUrl' => $this->websiteUrl(),
                'analyticsUrl' => route('admin.analytics.users', ['range' => 'custom',
                    'from' => $this->reports['period']['start'], 'to' => $this->reports['period']['end'],
                    'platform' => ['webapp', 'app'], 'audience' => 'registered']),
                'membershipsUrl' => route('admin.analytics.memberships'),
            ]);
    }
}
