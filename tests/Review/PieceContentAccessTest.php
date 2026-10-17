<?php

namespace Tests\Review;

use App\{Piece, Tag, Tutorial, User};
use App\Billing\Membership;
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PieceContentAccessTest extends ReviewTestCase
{
    protected $piece, $tutorial;

    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->piece = create(Piece::class, [
                'score_path' => 'test-score.pdf', 'score_url' => null,
                'audio_path' => 'full.mp3', 'audio_path_rh' => 'right.mp3', 'audio_path_lh' => 'left.mp3',
            ]);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short'] as $type => $name) {
                $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            $this->tutorial = create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'performance', 'video_url' => 'https://example.com/performance.mp4']);
            create(Tutorial::class, ['piece_id' => $this->piece->id, 'type' => 'synthesia', 'video_url' => 'https://example.com/synthesia.mp4']);
        });
        Storage::fake('public');
        Storage::disk('public')->put('test-score.pdf', 'test PDF fixture');
    }

    protected function userWithSubscription($sourceClass = null, array $attributes = [])
    {
        return Model::withoutEvents(function () use ($sourceClass, $attributes) {
            $user = create(User::class)->setAppends(['full_name']);
            if ($sourceClass) {
                $source = create($sourceClass, array_merge(['renews_at' => now()->addMonth(), 'created_at' => now()->subMonth()], $attributes));
                Membership::create(['user_id' => $user->id, 'source_id' => $source->id, 'source_type' => $sourceClass]);
            }
            return $user;
        });
    }

    protected function assertRestricted()
    {
        $prompt = auth('web')->guest() ? 'signup-score-modal' : 'piece-upgrade-modal';
        $guestFreePick = auth('web')->guest() && $this->piece->is_free;
        $videoLimit = auth('web')->guest() && ! $this->piece->is_free ? 0 : 10;
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('id="piece-performance"', false)->assertSee('id="piece-synthesia"', false)
            ->assertSee('id="score-preview"', false)->assertSee('score-preview-pages', false)
            ->assertSee('id="'.$prompt.'"', false)
            ->assertSee('/js/views/piece-access.js?id=', false)
            ->assertSee('Download score')->assertDontSee('<embed', false)
            ->assertDontSee('id="score-editor"', false);
        $document = new \DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//*[@id="score-preview" and contains(@class, "score-editor")]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="score-preview"]//*[contains(@class, "score-toolbar") and contains(@class, "d-none")]')->length);
        $controls = $xpath->query('//*[@id="score-preview"]//*[contains(@class, "score-toolbar") or contains(@class, "score-footer")]//button[not(ancestor::*[contains(@class, "dropdown-menu")])]');
        $this->assertSame(17, $controls->length);
        foreach ($controls as $control) {
            $this->assertFalse($control->hasAttribute('disabled'));
            if (strpos($control->getAttribute('class'), 'score-listen-button') !== false && $videoLimit > 0) {
                $this->assertSame('launch-audio', $control->getAttribute('id'));
                $this->assertFalse($control->hasAttribute('data-bs-toggle'));
                continue;
            }
            $this->assertSame('modal', $control->getAttribute('data-bs-toggle'));
            $target = auth('web')->guest() && strpos($control->getAttribute('class'), 'score-listen-button') !== false ? 'signup-audio-modal' : $prompt;
            if (auth('web')->guest() && $xpath->query('ancestor::*[contains(@class, "score-toolbar")]', $control)->length) $target = 'signup-annotations-modal';
            if (auth('web')->guest() && ($control->hasAttribute('data-print') || $control->getAttribute('aria-label') === 'Download score')) $target = 'signup-score-export-modal';
            $this->assertSame('#'.$target, $control->getAttribute('data-bs-target'));
        }
        $upgrade = $xpath->query('//*[@id="score-preview"]//*[contains(@class, "score-preview-upgrade")]')->item(0);
        $this->assertSame(auth('web')->guest() ? 0 : 1, $xpath->query('.//button', $upgrade)->length);
        $this->assertSame(0, $xpath->query('.//*[contains(@class, "signup-card")]', $upgrade)->length);
        $this->assertSame(1, $xpath->query('.//p', $upgrade)->length);
        if (auth('web')->guest()) {
            $signup = $xpath->query('.//a[contains(@class, "btn-primary")]', $upgrade)->item(0);
            $this->assertSame('Sign up free', trim($signup->textContent));
            $this->assertSame(route('register'), $signup->getAttribute('href'));
            $this->assertFalse($signup->hasAttribute('data-bs-toggle'), 'The score invitation goes straight to signup.');
            $this->assertSame(0, $xpath->query('.//ul', $upgrade)->length);
            $this->assertStringContainsString($this->piece->is_free ? 'This score is ready to explore' : 'this week’s score', $upgrade->textContent);
        } else {
            $this->assertStringContainsString('The full score is waiting for you', $upgrade->textContent);
        }
        $this->assertSame(auth('web')->guest() ? 1 : 0, $xpath->query('//*[@id="score-preview"]//a')->length);
        $this->assertSame($videoLimit > 0 ? 1 : 0, $xpath->query('//*[@id="score-preview"]//*[@id="launch-audio"]')->length);
        $this->assertSame('', $xpath->query('//*[@id="score-preview"]')->item(0)->getAttribute('data-annotations-url'));
        $response->assertDontSee('/js/views/score-editor.js?id=', false);
        $this->assertScoreExportControls($response, false);
        $this->assertSame((string) $videoLimit, $xpath->query('//*[@id="piece-performance"]')->item(0)->getAttribute('data-media-preview'));
        $this->assertSame((string) $videoLimit, $xpath->query('//*[@id="piece-synthesia"]')->item(0)->getAttribute('data-media-preview'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-preview="'.$videoLimit.'"'));
        $this->assertSame(auth('web')->guest() && ! $this->piece->is_free ? 1 : 0, substr_count($response->getContent(), 'data-audio-blocked="true"'));
        $response->assertSee('data-piece-player-page', false)->assertSee('data-player-close', false);
        $audio = $this->get(route('webapp.pieces.audio', $this->piece))->assertOk();
        $this->assertSame(3, substr_count($audio->getContent(), 'data-media-preview="'.$videoLimit.'"'));
        $tutorial = $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->tutorial]))->assertOk();
        $this->assertSame(1, substr_count($tutorial->getContent(), 'data-media-preview="'.$videoLimit.'"'));
        $synthesia = $this->piece->tutorials()->where('type', 'synthesia')->firstOrFail();
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $synthesia]))->assertOk()->assertSee('data-media-preview="'.$videoLimit.'"', false);
        $this->withExceptionHandling()->get(route('webapp.pieces.score', $this->piece))->assertForbidden();
        $this->exportAccessPreview($response);
        return $response;
    }

    public function test_piece_audio_band_has_shared_controls_and_only_the_main_recording()
    {
        $this->piece->updateQuietly(['is_free' => true]);
        $this->tutorial->moments()->create(['title' => 'Audio theme', 'start_time' => 0, 'sort_order' => 0, 'comment' => 'Audio detail stays out of the band.']);
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('data-piece-player-page', false)->assertDontSee('data-player-hands', false)
            ->assertSee('data-audio="'.storage('full.mp3').'"', false)
            ->assertDontSee('data-audio="'.storage('right.mp3').'"', false)->assertDontSee('data-audio="'.storage('left.mp3').'"', false)
            ->assertSee('data-player-close', false)->assertSee('data-speed="0.75"', false)
            ->assertSee('/js/views/playlist-player.js?id=', false)->assertSee('/js/views/playlist-moments.js?id=', false)
            ->assertSee('Audio theme')
            ->assertDontSee('piece-audio-popup', false)->assertSee('id="signup-audio-modal"', false);
        preg_match_all('/data-audio-moments="([^"]*)"/', $response->getContent(), $matches);
        $moments = array_map(function ($json) { return json_decode(html_entity_decode($json, ENT_QUOTES), true); }, $matches[1]);
        $this->assertCount(1, $moments);
        $this->assertSame('Audio theme', $moments[0][0]['title']);
        $this->assertArrayNotHasKey('comment', $moments[0][0]);
        $this->assertSame(1, substr_count($response->getContent(), 'data-playlist-player hidden'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-preview="10"'));
        $response->assertSee('score-preview-upgrade', false)->assertSee('score-editor--preview', false);
        if ($destination = getenv('PIECE_PLAYER_PREVIEW_DIR')) {
            $html = $response->getContent();
            $html = preg_replace('~(?:https?://[^/]+)?(/(?:css|js|images|fonts)/)~', '$1', $html);
            $html = str_replace('/storage/test-score.pdf', '/score.pdf', $html);
            foreach (['full', 'right', 'left'] as $audio) $html = str_replace('/storage/'.$audio.'.mp3', '/recording.wav', $html);
            $html = preg_replace('~https?://[^/]+(/(?:score\.pdf|recording\.wav))~', '$1', $html);
            file_put_contents($destination.'/piece.html', $html);
        }
        $this->piece->updateQuietly(['audio_path' => null]);
        $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertDontSee('data-piece-player-page', false)->assertDontSee('id="launch-audio"', false);
    }

    public function test_visitors_and_nonpaying_accounts_receive_the_same_content_restrictions()
    {
        $this->assertRestricted()->assertDontSee('data-manage="save-to"', false);
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->actingAs($this->userWithSubscription(), 'web');
        $this->assertRestricted()->assertSee('data-manage="save-to"', false);
        $this->get(route('webapp.my-pieces'))->assertOk()->assertSee('SUGGESTIONS');
        $this->postJson(route('webapp.users.favorites.update', $this->piece))->assertOk();
        $this->assertDatabaseCount('favorites', 1);
    }

    public function test_guests_get_action_specific_signup_prompts_and_members_keep_the_premium_prompt()
    {
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('Explore more scores')
            ->assertSee('Premium lets you read the full score')
            ->assertSee('data-bs-target="#signup-favorites-modal"', false)
            ->assertSee('data-preview-modal="signup-audio-modal"', false)
            ->assertDontSee('GO PREMIUM')->assertDontSee('Subscribe to read')
            ->assertDontSee('id="piece-upgrade-modal"', false);
        $document = new \DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $headerFavorite = $xpath->query('//section[.//*[contains(@class, "piece-header")]]//button[@data-bs-target="#signup-favorites-modal"]');
        $this->assertSame(1, $headerFavorite->length);
        $this->assertSame(0, $xpath->query('ancestor::*[contains(@class, "d-none")]', $headerFavorite->item(0))->length);
        foreach (['data-url', 'data-submit', 'data-manage'] as $attribute) $this->assertFalse($headerFavorite->item(0)->hasAttribute($attribute));
        foreach (['video' => '10-second media previews', 'audio' => '10-second media previews', 'score' => 'read the full score', 'annotations' => 'add and save your markings', 'score-export' => 'download and print scores', 'favorites' => 'save your favorite pieces', 'escore' => 'practice book'] as $action => $benefit) {
            $id = 'signup-'.$action.'-modal';
            $this->assertSame(1, $xpath->query('//*[@id="'.$id.'"]')->length);
            $modal = $xpath->query('//*[@id="'.$id.'"]')->item(0);
            $this->assertStringContainsString($benefit, $modal->textContent);
            $this->assertSame(1, $xpath->query('//*[@id="'.$modal->getAttribute('aria-labelledby').'"]')->length);
            $this->assertSame(1, $xpath->query('//*[@id="'.$modal->getAttribute('aria-describedby').'"]')->length);
            $signup = $xpath->query('.//a[contains(@class, "btn-primary")]', $modal);
            $this->assertSame(1, $signup->length);
            $this->assertSame('Sign up free', trim($signup->item(0)->textContent));
            $this->assertSame(route('register'), $signup->item(0)->getAttribute('href'));
            $this->assertSame(1, $xpath->query('.//a[@href="'.route('login').'"]', $modal)->length);
        }
        if ($directory = getenv('GUEST_SIGNUP_PREVIEW_DIR')) file_put_contents($directory.'/guest-piece.html', $response->getContent());
        $this->actingAs($this->userWithSubscription(), 'web');
        $member = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertSee('Go Premium')->assertSee('The full score is waiting for you')
            ->assertSee('data-bs-target="#piece-upgrade-modal"', false)
            ->assertSee('data-preview-modal="piece-upgrade-modal"', false)
            ->assertDontSee('id="signup-score-modal"', false);
        if ($directory = getenv('GUEST_SIGNUP_PREVIEW_DIR')) file_put_contents($directory.'/member-piece.html', $member->getContent());
    }

    public function test_active_stripe_and_apple_subscribers_and_trials_keep_full_content()
    {
        foreach ([
            [Stripe::class, []],
            [Apple::class, []],
            [Stripe::class, ['status' => 'trialing', 'created_at' => now(), 'renews_at' => now()->addDays(7)]],
            [Stripe::class, ['status' => 'trial', 'created_at' => now(), 'renews_at' => now()->addDays(7)]],
            [Apple::class, ['created_at' => now(), 'renews_at' => now()->addDays(7)]],
        ] as [$source, $attributes]) {
            $this->actingAs($this->userWithSubscription($source, $attributes), 'web');
            $this->assertFullContent();
        }
    }

    protected function assertScoreExportControls($response, $allowed)
    {
        $document = new \DOMDocument();
        $document->loadHTML($response->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $download = $xpath->query('//*[@aria-label="Download score"]');
        $print = $xpath->query('//*[@data-print]');
        $this->assertSame(1, $download->length);
        $this->assertSame(1, $print->length);
        $this->assertSame($allowed ? 'a' : 'button', $download->item(0)->tagName);
        $this->assertSame($allowed ? storage($this->piece->score_path) : '', $download->item(0)->getAttribute('href'));
        foreach ([$download->item(0), $print->item(0)] as $control) {
            $this->assertFalse($control->hasAttribute('disabled'));
            $this->assertSame($allowed ? '' : 'modal', $control->getAttribute('data-bs-toggle'));
            $this->assertSame($allowed ? '' : (auth('web')->guest() ? '#signup-score-export-modal' : '#piece-upgrade-modal'), $control->getAttribute('data-bs-target'));
        }
        $this->assertSame($allowed ? '1' : '0', $xpath->query('//*[@data-score-export-access]')->item(0)->getAttribute('data-score-export-access'));
        $this->assertSame($allowed || auth('web')->guest() ? 0 : 1, $xpath->query('//*[@id="piece-upgrade-modal"]')->length);
    }

    protected function assertFullContent($canExport = true)
    {
        $response = $this->get(route('webapp.pieces.show', $this->piece))->assertOk()
            ->assertDontSee('data-media-preview=', false)->assertDontSee('id="score-preview"', false)
            ->assertSee('Download score')->assertSee('id="score-pdf"', false)
            ->assertSee('id="score-editor"', false)->assertSee('data-tool="pen"', false)
            ->assertDontSee('data-audio-blocked="true"', false)
            ->assertSee('id="launch-audio"', false)->assertSee('data-piece-player-page', false)
            ->assertSee('playlist-player--piece', false)->assertDontSee('piece-audio-popup', false)
            ->assertDontSee('score-signup-title', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-preview="0"'));
        $this->get(route('webapp.pieces.audio', $this->piece))->assertOk()->assertDontSee('data-media-preview=', false);
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $this->tutorial]))->assertOk()->assertDontSee('data-media-preview=', false);
        $synthesia = $this->piece->tutorials()->where('type', 'synthesia')->firstOrFail();
        $this->get(route('webapp.pieces.tutorial', [$this->piece, $synthesia]))->assertOk()->assertDontSee('data-media-preview=', false);
        $this->assertScoreExportControls($response, $canExport);
        $this->withExceptionHandling()->get(route('webapp.pieces.score', $this->piece))->assertStatus($canExport ? 200 : 403);
        $this->exportAccessPreview($response);
        return $response;
    }

    protected function exportAccessPreview($response)
    {
        if (! $directory = getenv('MEDIA_FORMAT_PREVIEW_DIR')) return;
        $user = auth('web')->user();
        $account = ! $user ? 'guest' : ($user->hasActiveSubscription() ? 'member' : 'registered');
        file_put_contents($directory.'/'.$account.'-'.($this->piece->is_free ? 'free' : 'regular').'.html', $response->getContent());
    }

    public function test_super_users_keep_full_content_regardless_of_subscription_state()
    {
        foreach ([
            [null, []],
            [Stripe::class, ['ended_at' => now()]],
            [Stripe::class, ['renews_at' => now()->subDay()]],
            [Apple::class, ['renews_at' => now()->subDay()]],
        ] as [$source, $attributes]) {
            $user = $this->userWithSubscription($source, $attributes);
            $user->updateQuietly(['super_user' => true]);
            $this->actingAs($user, 'web');
            $this->assertTrue($user->isAuthorized());
            $this->assertFullContent();

            // Removing the override must restore the normal subscription rules.
            $user->updateQuietly(['super_user' => false]);
            $this->assertRestricted();
        }
    }

    public function test_grace_period_keeps_full_content_until_cancellation_takes_effect()
    {
        foreach (['active', 'trialing', 'canceled', 'paused'] as $status) {
            $user = $this->userWithSubscription(Stripe::class, [
                'status' => $status,
                'canceled_at' => now(),
                'paused_at' => $status === 'paused' ? now() : null,
                'renews_at' => null,
                'membership_ends_at' => now()->addDays(3),
            ]);
            $this->actingAs($user, 'web');
            $this->assertFullContent();

            // A stale renewal date must not extend a completed grace period.
            $user->membership->source->updateQuietly([
                'membership_ends_at' => now()->subSecond(),
                'renews_at' => now()->addMonth(),
            ]);
            $this->assertRestricted();
        }
    }

    public function test_inactive_or_unpaid_subscription_states_do_not_unlock_content()
    {
        foreach ([
            ['status' => 'trialing', 'renews_at' => now()->subDay()],
            ['status' => 'trialing', 'paused_at' => now()],
            ['status' => 'trialing', 'ended_at' => now()],
            ['status' => 'past_due'],
            ['paused_at' => now()],
            ['renews_at' => now()->subDay()],
            ['ended_at' => now()],
            ['ended_at' => now(), 'membership_ends_at' => now()->addDays(3)],
        ] as $attributes) {
            $this->actingAs($this->userWithSubscription(Stripe::class, $attributes), 'web');
            $this->assertRestricted();
        }
        $this->actingAs($this->userWithSubscription(Apple::class, ['renews_at' => now()->subDay()]), 'web');
        $this->assertRestricted();
    }

    public function test_free_pick_previews_unlock_full_media_scores_and_annotations_after_signup()
    {
        $this->piece->updateQuietly(['is_free' => true]);
        $guestFreePick = $this->assertRestricted()->assertDontSee('data-manage="save-to"', false)
            ->assertSee('data-annotations-url=""', false)
            ->assertSee('This score is ready to explore')->assertSee('read the full score.')
            ->assertDontSee('Sign up to read, mark and download');
        $document = new \DOMDocument();
        $document->loadHTML($guestFreePick->getContent(), LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($document);
        $this->assertSame(0, $xpath->query('//*[@id="score-preview"]//*[contains(@class, "score-toolbar") and contains(@class, "d-none")]')->length);
        $annotationControls = $xpath->query('//*[@id="score-preview"]//*[contains(@class, "score-toolbar")]//button[not(ancestor::*[contains(@class, "dropdown-menu")])]');
        $this->assertSame(9, $annotationControls->length);
        foreach ($annotationControls as $control) {
            $this->assertFalse($control->hasAttribute('disabled'));
            if (strpos($control->getAttribute('class'), 'score-listen-button') !== false && $videoLimit > 0) {
                $this->assertSame('launch-audio', $control->getAttribute('id'));
                $this->assertFalse($control->hasAttribute('data-bs-toggle'));
                continue;
            }
            $this->assertSame('modal', $control->getAttribute('data-bs-toggle'));
            $this->assertSame('#signup-annotations-modal', $control->getAttribute('data-bs-target'));
        }
        if ($directory = getenv('GUEST_SIGNUP_PREVIEW_DIR')) file_put_contents($directory.'/guest-free-pick.html', $guestFreePick->getContent());
        $this->assertDatabaseCount('recently_viewed_pieces', 0);
        $this->withExceptionHandling()->postJson(route('webapp.users.favorites.update', $this->piece))->assertUnauthorized();
        $this->getJson(route('webapp.pieces.score.annotations.show', $this->piece))->assertUnauthorized();
        $this->putJson(route('webapp.pieces.score.annotations.update', $this->piece), [])->assertUnauthorized();

        // Turning off the flag restores restrictions on the very next request.
        $this->piece->updateQuietly(['is_free' => false]);
        $this->assertRestricted();

        $this->actingAs($this->userWithSubscription(), 'web');
        $this->piece->updateQuietly(['is_free' => true]);
        $memberFreePick = $this->assertFullContent(false)->assertSee('data-manage="save-to"', false)
            ->assertSee('data-bs-toggle="offcanvas"', false)
            ->assertSee('id="save-to-offcanvas"', false)
            ->assertDontSee('data-annotations-url=""', false);
        if ($directory = getenv('GUEST_SIGNUP_PREVIEW_DIR')) file_put_contents($directory.'/member-free-pick.html', $memberFreePick->getContent());
        $this->postJson(route('webapp.users.favorites.update', $this->piece))->assertOk();
        $this->piece->updateQuietly(['is_free' => false]);
        $this->assertRestricted();
    }

    public function test_supplied_subscriber_identity_cannot_unlock_free_pick_downloads()
    {
        $subscriber = $this->userWithSubscription(Stripe::class);
        $this->piece->updateQuietly(['is_free' => true]);
        $url = route('webapp.pieces.score', [$this->piece, 'user_id' => $subscriber->id]);
        $this->withExceptionHandling()->get($url)->assertForbidden();
        $this->actingAs($this->userWithSubscription(), 'web')->get($url)->assertForbidden();
    }

    public function test_free_pick_does_not_unlock_exports_for_expired_memberships()
    {
        $this->piece->updateQuietly(['is_free' => true]);
        foreach ([Stripe::class, Apple::class] as $source) {
            $this->actingAs($this->userWithSubscription($source, ['renews_at' => now()->subDay()]), 'web');
            $this->assertFullContent(false);
        }
    }

    public function test_members_keep_every_free_pick_media_and_score_export()
    {
        $this->piece->updateQuietly(['is_free' => true]);
        foreach ([
            [Stripe::class, [], false],
            [Apple::class, [], false],
            [Stripe::class, ['status' => 'trialing', 'renews_at' => now()->addDays(7)], false],
            [Stripe::class, ['membership_ends_at' => now()->addDays(3), 'status' => 'canceled'], false],
            [null, [], true],
            [Stripe::class, ['ended_at' => now()], true],
        ] as [$source, $attributes, $superUser]) {
            $user = $this->userWithSubscription($source, $attributes);
            $user->updateQuietly(['super_user' => $superUser]);
            $this->actingAs($user, 'web');
            $this->assertFullContent();
        }
    }

    public function test_supplied_user_ids_do_not_bypass_normal_piece_content_rules()
    {
        $subscriber = $this->userWithSubscription(Stripe::class);
        $this->get(route('webapp.pieces.show', [$this->piece, 'user_id' => $subscriber->id]))
            ->assertOk()->assertSee('data-media-preview="0"', false)->assertSee('data-audio-blocked="true"', false);
        $audio = $this->get(route('webapp.pieces.audio', [$this->piece, 'user_id' => $subscriber->id]))->assertOk();
        $this->assertSame(3, substr_count($audio->getContent(), 'data-media-preview="0"'));
        $this->assertRestricted();
    }

    public function test_tutorial_must_belong_to_the_requested_piece()
    {
        $other = Model::withoutEvents(function () { return create(Piece::class); });
        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
        $this->get(route('webapp.pieces.tutorial', [$other, $this->tutorial]));
    }
}
