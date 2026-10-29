<?php

namespace Tests\Review;

use App\{EmailList, Subscription};
use App\CrashCourse\{CrashCourse, CrashCourseLesson};
use App\Mail\{CrashCourseEmail, CrashCourseFeedbackEmail, Gift};
use App\Mail\Newsletter\MonthlyDigestEmail;
use App\Services\Admin\AutomaticEmails;
use App\Support\EmailLayout;
use Illuminate\Mail\Markdown;

class CustomerEmailLayoutTest extends ReviewTestCase
{
    private function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    private function assertCustomerLayout($html, $text)
    {
        $xpath = $this->xpath($html);
        $logos = $xpath->query('//img[contains(@class,"editorial-logo-plain")]');
        $this->assertCount(1, $logos);
        foreach ($logos as $logo) {
            $this->assertStringContainsString('/images/emails/welcome/logo.png?id=', $logo->getAttribute('src'));
            $this->assertSame('48', $logo->getAttribute('width'));
        }
        $this->assertCount(1, $xpath->query('//td[contains(@class,"welcome-footer")]'));
        $this->assertCount(0, $xpath->query('//td[contains(@class,"welcome-footer")]//img'));
        $this->assertCount(1, $xpath->query('//td[contains(@class,"welcome-footer")]//a[normalize-space(.)="Visit us on youtube"]'));
        foreach (['Questions? Just reply', 'Privacy', 'Help', 'Forgot password', 'Visit us on youtube'] as $copy) {
            $this->assertStringContainsString($copy, $html);
            $this->assertStringContainsString($copy, $text);
        }
        foreach (['floating-logo.png', 'images/brand/app-icon.png', 'images/emails/youtube.png', 'instagram.png', 'reddit.png', '@endcomponent'] as $old) {
            $this->assertStringNotContainsString($old, $html);
        }
        $this->assertStringNotContainsString('<table', $text);
    }

    private function render($mail)
    {
        $mail->build();
        $markdown = clone app(Markdown::class);
        $markdown->theme($mail->theme ?: 'default');
        $data = $mail->buildViewData();
        return [(string) $markdown->render($mail->markdown, $data), (string) $markdown->renderText($mail->markdown, $data)];
    }

    public function test_every_customer_automatic_email_reuses_the_snapshot_logo_and_welcome_footer()
    {
        $catalog = app(AutomaticEmails::class);
        foreach ($catalog->all()->keys()->reject(fn ($key) => $key === 'admin-weekly-snapshot') as $key) {
            $sample = $catalog->render($key);
            $this->assertCustomerLayout($sample['html'], $sample['text']);
        }
        $snapshot = $catalog->render('admin-weekly-snapshot');
        foreach (['Visit us on youtube', 'Unsubscribe / email preferences', 'Questions? Just reply'] as $copy) {
            $this->assertStringNotContainsString($copy, $snapshot['html']);
            $this->assertStringNotContainsString($copy, $snapshot['text']);
        }
        $this->assertStringContainsString('A weekly snapshot for PianoLIT administrators', $snapshot['html']);
    }

    public function test_course_and_gift_bodies_keep_their_content_inside_the_shared_customer_layout()
    {
        $lesson = new CrashCourseLesson(['subject' => 'Lesson one', 'body' => '<p>Listen to this lesson.</p><p><a href="https://example.test/lesson">Watch the lesson</a></p>']);
        [$html, $text] = $this->render(new CrashCourseEmail($lesson));
        $this->assertCustomerLayout($html, $text);
        $this->assertStringContainsString('href="https://example.test/lesson"', $html);
        $this->assertStringContainsString('If you wish to stop receiving these emails', $text);
        $this->assertCount(1, $this->xpath($html)->query('//a[@href="mailto:contact@pianolit.com" and normalize-space(.)="Cancel this course"]'));

        [$html, $text] = $this->render(new CrashCourseFeedbackEmail(new CrashCourse(['title' => 'Sample course'])));
        $this->assertCustomerLayout($html, $text);
        $this->assertStringContainsString('https://forms.gle/a7Kz5T5gn5TfcD8f7', $html);

        [$html, $text] = $this->render(new Gift('https://example.test/gift', new Subscription(['email' => 'sample@example.test'])));
        $this->assertCustomerLayout($html, $text);
        $this->assertStringContainsString('href="https://example.test/gift"', $html);
    }

    public function test_list_and_newsletter_footers_preserve_recipient_specific_unsubscribe_destinations()
    {
        $list = new EmailList(['id' => 42, 'name' => EmailList::NEWSLETTER]);
        $subscription = new Subscription(['email' => 'sample@example.test']);
        $expected = EmailLayout::websiteUrl().route('subscriptions.unsubscribe', [$subscription, $list], false);
        $mail = new MonthlyDigestEmail('2026-10', collect(), $list, $subscription);
        [$html, $text] = $this->render($mail);
        $this->assertCustomerLayout($html, $text);
        $this->assertCount(1, $this->xpath($html)->query('//td[contains(@class,"welcome-footer")]//a[@href="'.$expected.'"]'));
        $this->assertSame(1, substr_count($text, $expected));
        $this->assertStringContainsString('Unsubscribe from the newsletter', $text);

        $markdown = clone app(Markdown::class);
        $data = compact('list', 'subscription');
        $html = (string) $markdown->theme('default')->render('emails.lists.test', $data);
        $text = (string) $markdown->renderText('emails.lists.test', $data);
        $this->assertCustomerLayout($html, $text);
        $this->assertStringContainsString($expected, $html);
        $this->assertStringContainsString($expected, $text);
    }

    public function test_queued_legacy_admin_reports_keep_their_existing_footer()
    {
        [$html, $text] = $this->render(new \App\Mail\Admin\AdminReport([], new \App\Admin(['first_name' => 'Sample'])));
        $this->assertStringContainsString('/images/brand/app-icon.png', $html);
        $this->assertStringContainsString('images/emails/facebook.png', $html);
        $this->assertStringNotContainsString('Visit us on youtube', $html);
        $this->assertStringNotContainsString('Unsubscribe / email preferences', $text);
    }
}
