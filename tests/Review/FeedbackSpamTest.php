<?php

namespace Tests\Review;

use App\{Admin, FeedbackSubmission, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

class FeedbackSpamTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([
            \App\Http\Middleware\Logs\RecordWebAppLog::class,
            \App\Http\Middleware\UpdateLocation::class,
        ]);
    }

    /** @dataProvider spamMessages */
    public function test_spam_is_rejected_before_feedback_or_admin_notifications_are_written($message)
    {
        create(Admin::class);
        $this->withExceptionHandling()->postJson(route('webapp.feedback.store'), [
            'rating' => 'good', 'message' => $message,
        ])->assertUnprocessable()->assertJsonValidationErrors('message')
            ->assertJsonPath('errors.message.0', 'Please send your feedback as plain text without website links or HTML.');

        $this->assertDatabaseCount('feedback_submissions', 0);
        $this->assertDatabaseCount('notifications', 0);
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public static function spamMessages()
    {
        return [
            'screenshot blog spam' => ['I have problems with your blog. Thank you xnxx. Also visit my blog ... webpage [<a href="https://creampie.xvideoszone.cc">creampie.xvideoszone.cc</a>]'],
            'screenshot website spam' => ['Hello there! Would you mind if I share your blog? Here is my website ... <a href="https://rough.xvideosporn.cc/">webpage</a>'],
            'ordinary website links are also excluded' => ['See https://example.test/reproduction'],
            'uppercase URL' => ['Visit HTTPS://EXAMPLE.TEST'],
            'bare domain' => ['Visit example.com for more information'],
            'bare screenshot domain' => ['Visit creampie.xvideoszone.cc'],
            'www' => ['Visit www.example.test'],
            'protocol relative' => ['Visit //example.test/path'],
            'IP address URL' => ['Visit http://127.0.0.1/path'],
            'bare IP address' => ['Visit 127.0.0.1/path'],
            'BBCode' => ['Thanks [URL=https://example.test]my site[/URL]'],
            'BBCode without destination' => ['Thanks [url]my website[/url]'],
            'markdown' => ['[My website](https://example.test)'],
            'HTML without a URL' => ['<script>alert("hello")</script>'],
            'HTML entity' => ['&lt;a href=&quot;https://example.test&quot;&gt;website&lt;/a&gt;'],
            'nested HTML entity' => ['&amp;lt;a href=&amp;quot;https://example.test&amp;quot;&amp;gt;website&amp;lt;/a&amp;gt;'],
            'encoded domain punctuation' => ['example&#46;test'],
            'percent encoded URL' => [rawurlencode('https://example.test')],
            'double percent encoded URL' => [rawurlencode(rawurlencode('https://example.test'))],
            'invisible characters' => ["ht\u{200B}tps://exa\u{200B}mple.te\u{200B}st"],
            'control characters' => ["example.te\x00st"],
            'Unicode dot' => ["example\u{3002}test"],
            'fullwidth URL' => ['ｈｔｔｐｓ：／／ｅｘａｍｐｌｅ．ｔｅｓｔ'],
            'international domain' => ['Visit 例子.测试'],
            'punycode domain' => ['Visit xn--fsqu00a.xn--0zwm56d'],
            'invalid decoded Unicode' => ['example.te%FFst'],
        ];
    }

    public function test_signed_in_accounts_cannot_bypass_the_message_policy()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web')->withExceptionHandling()->postJson(route('webapp.feedback.store'), [
            'rating' => 'bad', 'message' => 'Visit example.com',
        ])->assertUnprocessable()->assertJsonValidationErrors('message');
        $this->assertDatabaseCount('feedback_submissions', 0);
    }

    /** @dataProvider botFields */
    public function test_a_filled_or_malformed_bot_trap_prevents_saving_even_a_rating_only_submission($website)
    {
        create(Admin::class);
        $this->withExceptionHandling()->postJson(route('webapp.feedback.store'), [
            'rating' => 'good', 'website' => $website,
        ])->assertUnprocessable()->assertJsonValidationErrors('website');
        $this->assertDatabaseCount('feedback_submissions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public static function botFields()
    {
        return [['bot'], ['0'], [['bot']], [false], [123]];
    }

    /** @dataProvider legitimateMessages */
    public function test_plain_feedback_is_saved_unchanged_and_notifies_admins($message)
    {
        $admin = create(Admin::class);
        $this->postJson(route('webapp.feedback.store'), [
            'rating' => 'bad', 'message' => $message, 'website' => '',
        ])->assertCreated()->assertExactJson(['saved' => true]);
        $this->assertSame($message, FeedbackSubmission::sole()->message);
        $this->assertSame(1, $admin->notifications()->count());
    }

    public static function legitimateMessages()
    {
        return [
            ['The text runs off the screen. Can you fix it?'],
            ['This is frustrating. The audio will not play!'],
            ['Please fix Chopin’s Nocturne, Op. 9, No. 2 (e.g. the score).'],
            ['The score stops at bar 3 < 4 & the tempo is > 100.'],
            ["Merci ! Très utile 🎹\nMore Debussy, please."],
            ['0'],
            ['I am seeing spam on the website. Please remove it.'],
            [null],
        ];
    }

    public function test_the_modal_keeps_the_trap_out_of_keyboard_and_accessibility_navigation()
    {
        $html = view('webapp.feedback.modal')->render();
        $dom = new \DOMDocument; @$dom->loadHTML($html); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//div[@aria-hidden="true" and contains(@class,"d-none")]/input[@name="website" and @tabindex="-1" and @autocomplete="off"]')->length);
    }
}
