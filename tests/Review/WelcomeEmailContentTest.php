<?php

namespace Tests\Review;

use App\{FreePick, User};
use App\Mail\{AccountWelcome, IosAccountWelcome, WebsiteAccountWelcome};
use App\Services\{WelcomePieces, Admin\AutomaticEmails};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\DB;

class WelcomeEmailContentTest extends ReviewTestCase
{
    private function piece(array $attributes = [], bool $tutorial = true): int
    {
        $composer = DB::table('composers')->insertGetId([
            'name' => 'Sample Composer', 'biography' => '', 'cover_path' => '',
            'gender' => 'male', 'period' => 'romantic',
        ]);
        $id = DB::table('pieces')->insertGetId(array_merge([
            'name' => 'Sample piece', 'key' => 'C major', 'composer_id' => $composer,
            'cover_path' => 'pieces/sample.jpg', 'highlighted_at' => now()->subDays(2),
        ], $attributes));
        if ($tutorial) DB::table('tutorials')->insert([
            'piece_id' => $id, 'type' => 'performance', 'category' => 'full', 'filename' => 'sample.mp4',
        ]);
        return $id;
    }

    public function test_random_cards_are_distinct_past_picks_excluding_the_unsynced_current_pick()
    {
        $past = [];
        for ($i = 0; $i < 6; $i++) $past[] = $this->piece(['name' => 'Past pick '.$i]);
        $current = $this->piece(['name' => 'Current pick']);
        FreePick::create(['piece_id' => $current, 'scheduled_at' => now('UTC')->subMinute()]);
        $this->piece(['name' => 'Never highlighted', 'highlighted_at' => null]);
        $this->piece(['name' => 'Future highlight', 'highlighted_at' => now()->addDay()]);
        $this->piece(['name' => 'No image', 'cover_path' => null]);
        $this->piece(['name' => 'Empty image', 'cover_path' => '']);
        $this->piece(['name' => 'No tutorial'], false);
        // A future scheduled pick must not displace the current due one.
        FreePick::create(['piece_id' => $past[0], 'scheduled_at' => now('UTC')->addDay()]);

        $selections = [];
        for ($i = 0; $i < 12; $i++) {
            $cards = app(WelcomePieces::class)->random('https://pianolit.example');
            $this->assertCount(3, $cards);
            $urls = array_column($cards, 'url');
            $this->assertCount(3, array_unique($urls));
            foreach ($cards as $card) {
                $this->assertContains($card['url'], array_map(function ($id) { return route('webapp.pieces.show', $id); }, $past));
                $this->assertSame('https://pianolit.example/storage/pieces/sample.jpg', $card['image']);
            }
            sort($urls);
            $selections[] = implode(',', $urls);
        }
        $this->assertGreaterThan(1, count(array_unique($selections)), 'Selection should vary between messages.');
    }

    public function test_actual_and_serialized_welcomes_share_the_same_cards_in_html_and_text()
    {
        for ($i = 0; $i < 3; $i++) $this->piece(['name' => 'Past & loved '.$i]);
        foreach ([AccountWelcome::class, WebsiteAccountWelcome::class, IosAccountWelcome::class] as $class) {
            $mail = unserialize(serialize(new SendQueuedMailable(new $class('<Jamie>'))))->mailable;
            $mail->build();
            $cards = $mail->welcomePieces;
            $renderer = clone app(\Illuminate\Mail\Markdown::class);
            $renderer->theme('welcome');
            $html = (string) $renderer->render($mail->markdown, $mail->buildViewData());
            $this->assertStringContainsString('Welcome, &lt;Jamie&gt;', $html);
            $this->assertSame($cards, $mail->welcomePieces);
            $text = (string) $renderer->renderText($mail->markdown, $mail->buildViewData());
            foreach ($cards as $card) {
                $this->assertStringContainsString(e($card['title']), $html);
                $this->assertStringContainsString($card['title'], $text);
                $this->assertStringContainsString($card['url'], $html);
                $this->assertStringContainsString($card['url'], $text);
            }
            $this->assertStringContainsString('Unsubscribe / email preferences', $text);
        }
    }

    public function test_small_or_empty_history_does_not_duplicate_or_invent_pieces()
    {
        $this->assertSame([], app(WelcomePieces::class)->random('https://pianolit.example'));
        $mail = (new AccountWelcome('Jamie'))->build();
        $renderer = clone app(\Illuminate\Mail\Markdown::class);
        $this->assertStringNotContainsString('welcome-piece-list', (string) $renderer->theme('welcome')->render($mail->markdown, $mail->buildViewData()));
        $this->piece();
        $this->assertCount(1, app(WelcomePieces::class)->random('https://pianolit.example'));
    }

    public function test_shared_lower_sections_match_across_all_three_admin_previews()
    {
        $sections = [];
        foreach (['account-welcome-website', 'account-welcome', 'account-welcome-ios'] as $key) {
            $sample = app(AutomaticEmails::class)->render($key);
            $dom = new \DOMDocument; @$dom->loadHTML($sample['html']);
            $xpath = new \DOMXPath($dom);
            $sections[] = array_map(function ($node) use ($dom) { return $dom->saveHTML($node); }, iterator_to_array($xpath->query('//td[contains(@class,"welcome-hubs") or contains(@class,"welcome-benefits") or contains(@class,"welcome-pieces") or contains(@class,"welcome-footer")]')));
            $this->assertCount(5, $xpath->query('//td[@class="welcome-hub-image"]/following-sibling::td[@class="welcome-hub-copy"]'));
            $this->assertCount(4, $xpath->query('//img[@class="welcome-benefit-icon"]'));
            $this->assertCount(3, $xpath->query('//td[@class="welcome-piece"]'));
        }
        $this->assertSame($sections[0], $sections[1]);
        $this->assertSame($sections[0], $sections[2]);
    }

    public function test_unsubscribe_link_opens_the_existing_email_preferences_for_the_signed_in_account()
    {
        config(['activity.enabled' => false, 'attribution.enabled' => false]);
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $response = $this->get(route('users.profile').'?tab=email-preferences')->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//section[@id="email-preferences"]'));
        $this->assertCount(1, $xpath->query('//nav[@aria-label="Profile sections"]/a[@href="#email-preferences"]'));
        $this->get(route('users.profile'))->assertOk()->assertSee('Personal information');
    }
}
