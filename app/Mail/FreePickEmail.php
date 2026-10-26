<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\{Piece, EmailList};
use App\Mail\Traits\{Trackable, EditorialLayout};

class FreePickEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels, Trackable, EditorialLayout;

    public $theme = 'welcome';

    public $piece, $subscription, $list, $list_id, $tutorials;

    public $latestPieces, $videoCommentsAvailable;

    public function __construct($list_id, $subscription = null, ?Piece $piece = null)
    {
        $this->piece = $piece ?: Piece::free()->first();
        $this->tutorials = $this->piece->relationLoaded('tutorials')
            ? $this->piece->tutorials->toArray() : $this->piece->tutorials()->get()->toArray();
        $this->subscription = $subscription;
        $this->list = EmailList::freepick();
        $this->list_id = $list_id;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $this->track($this->list, $this->list_id, $this->subscription);

        $website = $this->websiteUrl();
        if ($this->latestPieces === null) {
            $this->latestPieces = app(\App\Services\FreePickEmailPieces::class)->latest($website, (int) $this->piece->id);
        }
        $hasVideo = function ($type) {
            return collect($this->tutorials)->contains(function ($tutorial) use ($type) {
                return stripos($tutorial['type'] ?? '', $type) !== false && !empty($tutorial['video_url']);
            });
        };
        if ($this->videoCommentsAvailable === null) {
            // Match the first performance/Synthesia videos selected by the piece
            // player, rather than advertising comments on a different recording.
            $videoIds = collect(['performance', 'synthesia'])->map(function ($type) {
                return collect($this->tutorials)->first(function ($tutorial) use ($type) {
                    return stripos($tutorial['type'] ?? '', $type) !== false;
                });
            })->filter(function ($video) { return !empty($video['video_url']); })
                ->pluck('id')->filter()->all();
            $this->videoCommentsAvailable = $videoIds && \App\VideoMoment::whereIn('tutorial_id', $videoIds)
                ->where('comment', '<>', '')->exists();
        }
        $preferences = $website.route('users.profile', [], false).'?tab=email-preferences';

        return $this->subject('Your free pick this week')->markdown('emails.lists.freepick', [
            'websiteUrl' => $website,
            'pieceUrl' => route('webapp.pieces.show', $this->piece->id),
            'premiumUrl' => route('webapp.membership.pricing'),
            'libraryUrl' => route('webapp.explore'),
            'unsubscribeUrl' => $this->subscription && $this->list
                ? $website.route('subscriptions.unsubscribe', [$this->subscription, $this->list], false)
                : $preferences,
            'hasScore' => (bool) $this->piece->score_path && $this->piece->is_public_domain,
            'hasPerformance' => $hasVideo('performance'),
            'hasSynthesia' => $hasVideo('synthesia'),
            'hasAudio' => $this->piece->hasAudio(),
            'hasSeparateHands' => (bool) ($this->piece->audio_path_rh || $this->piece->audio_path_lh),
            'hasVideoComments' => $this->videoCommentsAvailable,
        ]);
    }

    public function tags()
    {
        return ['email lists', 'free pick'];
    }
}
