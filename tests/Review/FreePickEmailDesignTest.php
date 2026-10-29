<?php

namespace Tests\Review;

use App\{Composer, EmailList, Piece, Subscription, Tag, Tutorial};
use App\Mail\FreePickEmail;
use App\Services\Admin\AutomaticEmails;
use App\Services\FreePickCampaignLinks;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\{Mailer, Markdown, SendQueuedMailable};
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\DB;

class FreePickEmailDesignTest extends ReviewTestCase
{
    private function piece(array $attributes = [], array $videos = ['Performance', 'Synthesia']): Piece
    {
        return Model::withoutEvents(function () use ($attributes, $videos) {
            $composer = create(Composer::class, ['name' => 'Cécile Chaminade', 'cover_path' => 'composer/portrait.jpg']);
            $piece = create(Piece::class, array_merge([
                'name' => 'Adagio Patetico', 'catalogue_name' => 'Op.', 'catalogue_number' => '61',
                'collection_name' => null, 'collection_number' => null, 'movement_number' => null,
                'composer_id' => $composer->id, 'description' => 'A lyrical discovery. <script>unsafe</script>',
                'cover_path' => 'piece/cover.jpg', 'score_path' => 'scores/piece.pdf',
                'audio_path' => 'audio/piece.mp3', 'audio_path_rh' => 'audio/right.mp3',
            ], $attributes));
            $piece->tags()->attach(create(Tag::class, ['name' => 'advanced', 'type' => 'level']));
            foreach ($videos as $type) create(Tutorial::class, [
                'piece_id' => $piece->id, 'type' => $type, 'video_url' => 'https://media.example/recording.mp4',
            ]);
            return $piece;
        });
    }

    private function render($piece, $subscriber = null, $campaign = 'preview', $legacyData = false)
    {
        EmailList::firstOrCreate(['name' => EmailList::FREE_PICK], ['description' => 'Free weekly picks']);
        $mail = new FreePickEmail($campaign, $subscriber, $piece);
        $mail->build();
        $renderer = clone app(Markdown::class);
        $renderer->theme($mail->theme);
        $data = $mail->buildViewData();
        if ($legacyData) unset($data['campaignLinks']);
        $html = (string) $renderer->render($mail->markdown, $data);
        $text = (string) $renderer->renderText($mail->markdown, $data);
        $dom = new \DOMDocument; @$dom->loadHTML($html);
        return [$html, $text, new \DOMXPath($dom), $dom];
    }

    public function test_piece_identity_and_available_resources_are_present_with_safe_piece_links()
    {
        $piece = $this->piece();
        [$html, $text, $xpath] = $this->render($piece);
        $this->assertCount(1, $xpath->query('//td[contains(@class,"freepick-header")]//img[contains(@class,"editorial-logo-plain")]'));
        $this->assertCount(1, $xpath->query('//td[contains(@class,"freepick-header")]//h1[@class="freepick-heading"]'));
        $this->assertCount(1, $xpath->query('//td[@class="freepick-header"]/parent::tr/following-sibling::tr[1]/td[@class="freepick-cover"]'));
        $this->assertCount(4, $xpath->query('//td[@class="editorial-feature-icon"]'));
        $this->assertCount(1, $xpath->query('//span[contains(@class,"freepick-level-advanced")]'));
        foreach (['Free pick of the week', "Let's dive in", $piece->medium_name, 'Cécile Chaminade', 'annotations',
            'Synthesia', 'separate-hand audio', 'PianoLIT account', 'Go Premium'] as $copy) {
            $this->assertStringContainsString($copy, html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $this->assertStringContainsString($copy, $text);
        }
        $this->assertStringContainsString('&lt;script&gt;unsafe&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('href="'.route('webapp.pieces.show', $piece->id).'"', $html);
        $this->assertStringContainsString(route('webapp.pieces.show', $piece->id), $text);
        $this->assertStringNotContainsString('Stage', $html);
        $this->assertStringNotContainsString('FREE eBooks', $html);
        foreach (['Prefer your phone?', 'Open the iOS app', 'free PianoLIT account', 'Lessons &amp; insights'] as $removed) {
            $this->assertStringNotContainsString($removed, $html);
            $this->assertStringNotContainsString(html_entity_decode($removed), $text);
        }
        $this->assertStringNotContainsString('<table', $text);
    }

    public function test_feature_rows_follow_the_actual_media_and_do_not_promise_unavailable_assets()
    {
        $piece = $this->piece(['score_path' => null, 'score_url' => 'https://scores.example/buy',
            'audio_path' => null, 'audio_path_rh' => null, 'audio_path_lh' => null], ['Harmonic analysis']);
        [$html, $text, $xpath] = $this->render($piece);
        $this->assertCount(0, $xpath->query('//td[@class="editorial-feature-icon"]'));
        foreach (['Score & annotations', 'Performance video', 'Synthesia', 'Audio for practice'] as $copy) {
            $this->assertStringNotContainsString(e($copy), $html);
            $this->assertStringNotContainsString($copy, $text);
        }
        $this->assertStringNotContainsString('Lessons &amp; insights', $html);
        $this->assertStringNotContainsString('Lessons & insights', $text);
        $piece->tutorials()->delete();
        [$html] = $this->render($piece->fresh());
        $this->assertStringNotContainsString('freepick-features', $html);
    }

    public function test_footer_uses_the_same_component_and_preserves_real_unsubscribe_and_inert_previews()
    {
        $piece = $this->piece();
        $subscriber = Subscription::create(['email' => 'reader@example.com']);
        [$html, $text, $xpath, $dom] = $this->render($piece, $subscriber);
        $unsubscribe = route('subscriptions.unsubscribe', [$subscriber, EmailList::freepick()]);
        $this->assertStringContainsString($unsubscribe, $html);
        $this->assertStringContainsString($unsubscribe, $text);
        $this->assertDatabaseCount('email_logs', 0);
        [$html] = $this->render($piece);
        $this->assertStringNotContainsString('/unsubscribe/', $html);
        $this->assertStringContainsString('?tab=email-preferences', $html);

        $welcome = app(AutomaticEmails::class)->render('account-welcome');
        $welcomeDom = new \DOMDocument; @$welcomeDom->loadHTML($welcome['html']);
        $welcomeXpath = new \DOMXPath($welcomeDom);
        $footer = $xpath->query('//td[contains(@class,"welcome-footer")]')->item(0);
        $welcomeFooter = $welcomeXpath->query('//td[contains(@class,"welcome-footer")]')->item(0);
        foreach (['style', 'class'] as $attribute) $this->assertSame($welcomeFooter->getAttribute($attribute), $footer->getAttribute($attribute));
        foreach (['Questions? Just reply', 'Privacy', 'Help', 'Forgot password'] as $copy) {
            $this->assertStringContainsString($copy, $footer->textContent);
            $this->assertStringContainsString($copy, $welcomeFooter->textContent);
        }
    }

    public function test_video_comments_are_promoted_only_for_the_recordings_the_piece_player_uses()
    {
        $piece = $this->piece();
        $otherRecording = Model::withoutEvents(function () use ($piece) {
            return create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'video_url' => 'https://media.example/other.mp4']);
        });
        DB::table('video_moments')->insert(['tutorial_id' => $otherRecording->id,
            'start_time' => 0, 'title' => 'A different recording', 'comment' => 'Other commentary']);
        [$html, $text] = $this->render($piece);
        $this->assertStringNotContainsString('Comments as you watch', $html);
        $this->assertStringNotContainsString('Comments as you watch', $text);

        $performance = $piece->tutorials()->where('type', 'Performance')->orderBy('id')->first();
        $comment = DB::table('video_moments')->insertGetId(['tutorial_id' => $performance->id,
            'start_time' => 0, 'title' => 'Listen to the melody', 'comment' => 'Follow the singing phrase.']);
        [$html, $text, $xpath] = $this->render($piece);
        $this->assertCount(5, $xpath->query('//td[@class="editorial-feature-icon"]'));
        foreach (['Comments as you watch', 'in sync with the video', 'passages they explain'] as $copy) {
            $this->assertStringContainsString($copy, $html);
            $this->assertStringContainsString($copy, $text);
        }
        DB::table('video_moments')->where('id', $comment)->update(['comment' => '']);
        [$html] = $this->render($piece);
        $this->assertStringNotContainsString('Comments as you watch', $html);
    }

    public function test_normal_freepick_delivery_does_not_change_the_next_ordinary_email_theme()
    {
        $transport = new ArrayTransport;
        $mailer = new Mailer('freepick-theme-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('preview@example.com', 'PianoLIT');
        $this->app->instance('mailer', $mailer);
        $ordinary = (new \App\Mail\ProfileEmailChange('#email-preview'))->render();
        EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'Free weekly picks']);
        (new FreePickEmail('preview', null, $this->piece()))->to('reader@example.com')->send($mailer);
        $this->assertStringContainsString('freepick-heading', $transport->messages()->last()->getBody());
        $this->assertSame($ordinary, (new \App\Mail\ProfileEmailChange('#email-preview'))->render());
    }

    public function test_latest_cards_are_ordered_distinct_and_exclude_the_featured_or_incomplete_pieces()
    {
        $featured = $this->piece();
        $old = $this->piece(['name' => 'Older piece', 'created_at' => now()->subDays(10)]);
        $middle = $this->piece(['name' => 'Middle piece', 'created_at' => now()->subDay()]);
        $first = $this->piece(['name' => 'First latest', 'created_at' => now()]);
        $second = $this->piece(['name' => 'Second latest', 'created_at' => now()]);
        $this->piece(['cover_path' => null, 'created_at' => now()->addMinute()]);
        $this->piece(['created_at' => now()->addMinute()], []);

        $cards = app(\App\Services\FreePickEmailPieces::class)->latest('https://pianolit.example', $featured->id);
        $urls = array_map(function ($piece) { return route('webapp.pieces.show', $piece->id); }, [$second, $first, $middle]);
        $this->assertSame($urls, array_column($cards, 'url'));
        $this->assertCount(3, array_unique(array_column($cards, 'url')));
        [$html, $text, $xpath] = $this->render($featured);
        $this->assertCount(3, $xpath->query('//td[contains(@class,"freepick-latest")]//td[@class="welcome-piece"]'));
        foreach ($urls as $url) {
            $this->assertStringContainsString($url, $html);
            $this->assertStringContainsString($url, $text);
        }
        $this->assertStringContainsString('href="'.route('webapp.explore').'"', $html);
        $this->assertStringContainsString('Browse the PianoLIT library', $text);
    }

    public function test_only_the_three_body_buttons_get_freepick_campaign_tags_in_both_formats()
    {
        $piece = $this->piece();
        $this->piece(['name' => 'Another discovery']);
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        [$html, $text, $xpath] = $this->render($piece, $subscriber, 'free-pick.123');
        $expected = [
            'Explore this piece' => ['explore_piece', route('webapp.pieces.show', $piece->id)],
            'Browse the PianoLIT library' => ['browse_library', route('webapp.explore')],
            'Go Premium' => ['go_premium', route('webapp.membership.pricing')],
        ];
        $tagged = [];
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $url = $anchor->getAttribute('href');
            $label = trim($anchor->textContent);
            if (isset($expected[$label])) {
                [$placement, $destination] = $expected[$label];
                parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $parameters);
                $this->assertSame(['utm_source' => 'pianolit', 'utm_medium' => 'email', 'utm_campaign' => 'freepick', 'utm_content' => $placement], $parameters);
                $this->assertSame($destination, strtok($url, '?'));
                $this->assertStringContainsString($url, $text);
                $tagged[] = $label;
            } else {
                $this->assertStringNotContainsString('utm_', $url, $label ?: 'Image link');
            }
        }
        $this->assertEqualsCanonicalizing(array_keys($expected), $tagged);
        $unsubscribe = route('subscriptions.unsubscribe', [$subscriber, EmailList::freepick()]);
        $this->assertSame($unsubscribe, $xpath->query('//a[normalize-space(.)="Unsubscribe"]')->item(0)->getAttribute('href'));
        $this->assertStringContainsString($unsubscribe, $text);
        $this->assertSame(3, substr_count($text, 'utm_campaign=freepick'));
        $this->assertStringNotContainsString('campaigns/freepick/', $html);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_previews_and_excluded_recipients_have_no_campaign_tags()
    {
        $piece = $this->piece();
        $this->piece(['name' => 'Another discovery']);
        foreach ([' ARTHURVILLAR@gmail.com ', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $email) {
            $subscriber = Subscription::create(compact('email'));
            [$html, $text] = $this->render($piece, $subscriber, 'free-pick.123');
            $this->assertStringNotContainsString('utm_', $html);
            $this->assertStringNotContainsString('utm_', $text);
        }
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        [$html, $text] = $this->render($piece, $subscriber);
        $this->assertStringNotContainsString('utm_', $html);
        $this->assertStringNotContainsString('utm_', $text);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('email_logs', 0);
    }

    public function test_new_template_handles_mail_data_from_workers_started_before_campaign_links_were_added()
    {
        $piece = $this->piece();
        $this->piece(['name' => 'Another discovery']);
        $reader = Subscription::create(['email' => 'reader@example.test']);
        $cases = [[null, 'preview', false], [$reader, 'preview', false], [$reader, 'free-pick.legacy', true]];
        foreach ([' ARTHURVILLAR@gmail.com ', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $email) {
            $cases[] = [Subscription::create(compact('email')), 'free-pick.legacy', false];
        }
        foreach ($cases as [$subscriber, $campaign, $tagged]) {
            [$html, $text] = $this->render($piece, $subscriber, $campaign, true);
            $this->assertStringContainsString('Explore this piece', $html);
            $this->assertSame($tagged ? 3 : 0, substr_count($html, 'utm_campaign=freepick'));
            $this->assertSame($tagged ? 3 : 0, substr_count($text, 'utm_campaign=freepick'));
        }
        $this->assertDatabaseCount('email_logs', 0);
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_serialized_subscriber_delivery_keeps_campaign_buttons_and_existing_mailgun_identity()
    {
        $piece = $this->piece();
        $this->piece(['name' => 'Another discovery']);
        EmailList::create(['name' => EmailList::FREE_PICK, 'description' => 'Free weekly picks']);
        $subscriber = Subscription::create(['email' => 'reader@example.test']);
        $job = unserialize(serialize(new SendQueuedMailable(new FreePickEmail('free-pick.123', $subscriber, $piece))));
        $transport = new ArrayTransport;
        $mailer = new Mailer('campaign-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('sender@example.test', 'PianoLIT');
        $job->mailable->to($subscriber->email)->send($mailer);
        $message = $transport->messages()->sole();
        $this->assertSame(3, substr_count($message->getBody(), 'utm_campaign=freepick'));
        $this->assertSame(\App\Services\MailgunEvents::campaignTag('free-pick.123'), $message->getHeaders()->get('X-Mailgun-Tag')->getFieldBody());
        $this->assertDatabaseHas('email_logs', ['list_id' => 'free-pick.123', 'recipient' => $subscriber->email]);
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_campaign_url_preserves_other_parameters_and_fragment_without_duplicate_tags()
    {
        $links = new FreePickCampaignLinks(true);
        $url = $links->url('https://my.pianolit.test/explore?search=A%20%26%20B&utm_campaign=older#results', 'browse_library');
        parse_str(parse_url($url, PHP_URL_QUERY), $parameters);
        $this->assertSame('A & B', $parameters['search']);
        $this->assertSame('freepick', $parameters['utm_campaign']);
        $this->assertSame('results', parse_url($url, PHP_URL_FRAGMENT));
        $this->assertSame(1, substr_count($links->url($url, 'browse_library'), 'utm_campaign='));
        $this->assertSame('#email-preview', $links->url('#email-preview', 'explore_piece'));
    }
}
