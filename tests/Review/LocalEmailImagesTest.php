<?php

namespace Tests\Review;

use App\Mail\{AccountWelcome, IosAccountWelcome, WebsiteAccountWelcome};
use App\Mail\Admin\EmailPreview;
use App\Services\Admin\AutomaticEmails;
use Illuminate\Mail\{Mailer, SendQueuedMailable};
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Http;

class LocalEmailImagesTest extends ReviewTestCase
{
    private function local()
    {
        $this->app['env'] = 'local';
        config(['app.url' => 'pianolit.test']);
    }

    private function deliver($mail)
    {
        $transport = new ArrayTransport;
        $mailer = new Mailer('local-image-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
        $mailer->alwaysFrom('preview@example.com', 'PianoLIT');
        $mail->to('jamie@example.com')->send($mailer);
        return $transport->messages()->last();
    }

    private function images($message)
    {
        return array_values(array_filter($message->getChildren(), function ($part) { return $part instanceof \Swift_Image; }));
    }

    private function assertWelcomeImages($message, $hasAppStoreBadge, $hasSampleCards = false)
    {
        $images = $this->images($message);
        $this->assertCount(($hasAppStoreBadge ? 14 : 13) + ($hasSampleCards ? 3 : 0), $images);
        $filenames = array_map(function ($image) { return $image->getFilename(); }, $images);
        $this->assertSame($hasAppStoreBadge, in_array('app-store.png', $filenames, true));
        $this->assertStringNotContainsString('src="http', $message->getBody());
        foreach ($images as $image) {
            $this->assertSame('inline', $image->getDisposition());
            $this->assertStringContainsString('src="cid:'.$image->getId().'"', $message->getBody());
            $directory = $image->getFilename() === 'youtube.png' ? 'images/emails/' : 'images/emails/welcome/';
            $this->assertSame(file_get_contents(public_path($directory.$image->getFilename())), $image->getBody());
        }
        $raw = $message->toString();
        $this->assertStringContainsString('multipart/related;', $raw);
        $this->assertStringContainsString('Content-Type: text/plain;', $raw);
        $this->assertStringContainsString('Content-Type: text/html;', $raw);
        $this->assertStringContainsString('href="http://pianolit.test"', $message->getBody());
    }

    public function test_local_sent_previews_include_images_while_browser_previews_keep_urls()
    {
        $this->local();
        foreach (['account-welcome-website', 'account-welcome', 'account-welcome-ios'] as $key) {
            $sample = app(AutomaticEmails::class)->render($key);
            $this->assertStringContainsString('src="http://pianolit.test/images/', $sample['html']);
            $this->assertStringNotContainsString('cid:', $sample['html']);
            $message = $this->deliver(new EmailPreview($sample['subject'], $sample['html'], $sample['text']));
            $this->assertWelcomeImages($message, $key !== 'account-welcome-ios', true);
            $plain = array_values(array_filter($message->getChildren(), function ($part) { return $part->getBodyContentType() === 'text/plain'; }));
            $this->assertCount(1, $plain);
            $this->assertSame($sample['text'], $plain[0]->getBody());
        }
        Http::assertNothingSent();
    }

    public function test_normal_and_serialized_welcome_deliveries_include_the_same_local_images()
    {
        $this->local();
        foreach (['account-welcome-website' => WebsiteAccountWelcome::class, 'account-welcome' => AccountWelcome::class, 'account-welcome-ios' => IosAccountWelcome::class] as $key => $class) {
            app(\App\Mail\AutomaticEmailSettings::class)->setEnabled($key, true, null);
            $job = unserialize(serialize(new SendQueuedMailable(new $class('Jamie'))));
            $this->assertWelcomeImages($this->deliver($job->mailable), $key !== 'account-welcome-ios');
        }
    }

    public function test_production_and_non_test_hosts_keep_remote_images_unchanged()
    {
        foreach ([['production', 'https://pianolit.com'], ['production', 'http://pianolit.test'], ['local', 'https://pianolit.com']] as [$environment, $root]) {
            $this->app['env'] = $environment;
            config(['app.url' => $root]);
            $html = '<img src="http://pianolit.test/images/brand/app-icon.png"><a href="'.$root.'">Home</a>';
            $message = $this->deliver(new EmailPreview('Example', $html, 'Example'));
            $this->assertSame($html, $message->getBody());
            $this->assertCount(0, $this->images($message));
        }
    }

    public function test_only_existing_public_images_are_embedded_and_duplicate_references_share_one_part()
    {
        $this->local();
        $html = '<a href="http://pianolit.test/images/brand/app-icon.png">Keep the link</a>'
            .'<img src="http://pianolit.test/images/brand/app-icon.png?id=one&amp;v=2">'
            ."<img alt='Same image' src='/images/brand/app-icon.png?id=two'>"
            .'<img src="https://my.pianolit.test/images/brand/app-icon.png">';
        $unchanged = [
            'https://external.example/images/brand/app-icon.png',
            'http://pianolit.test.evil.example/images/brand/app-icon.png',
            'file://'.public_path('images/brand/app-icon.png'),
            'data:image/png;base64,AA==', 'cid:already-embedded',
            '/images/missing.png', '/images/../../.env',
            '/images/%2e%2e/%2e%2e/composer.json', '/images/invalid%00.png', '/js/app.js',
        ];
        foreach ($unchanged as $url) $html .= '<img src="'.$url.'">';
        $html .= '<img data-src="/images/brand/app-icon.png">';
        $message = $this->deliver(new EmailPreview('Paths', $html, 'Plain stays unchanged'));
        $images = $this->images($message);
        $this->assertCount(1, $images);
        $this->assertSame(3, substr_count($message->getBody(), 'cid:'.$images[0]->getId()));
        foreach ($unchanged as $url) $this->assertStringContainsString('src="'.$url.'"', $message->getBody());
        $this->assertStringContainsString('href="http://pianolit.test/images/brand/app-icon.png"', $message->getBody());
        $this->assertStringContainsString('data-src="/images/brand/app-icon.png"', $message->getBody());
        Http::assertNothingSent();
    }

    public function test_symlinks_and_encoded_paths_cannot_attach_files_outside_public_images()
    {
        $this->local();
        $fixture = sys_get_temp_dir().'/pianolit-mail-images-'.bin2hex(random_bytes(6));
        mkdir($fixture.'/images', 0700, true);
        $outside = $fixture.'/outside.png';
        copy(public_path('images/brand/app-icon.png'), $outside);
        copy($outside, $fixture.'/images/safe.png');
        symlink($outside, $fixture.'/images/escape.png');
        file_put_contents($fixture.'/images/not-an-image.png', 'private text');
        $original = public_path();
        $this->app->instance('path.public', $fixture);
        try {
            $html = '<img src="/images/escape.png"><img src="/images/../outside.png">'
                .'<img src="/images/%2e%2e/outside.png"><img src="/images/not-an-image.png">';
            $message = $this->deliver(new EmailPreview('Paths', $html.'<img src="/images/safe.png">', 'Text'));
            $this->assertStringStartsWith($html, $message->getBody());
            $images = $this->images($message);
            $this->assertCount(1, $images);
            $this->assertSame('safe.png', $images[0]->getFilename());
            $this->assertSame(file_get_contents($outside), $images[0]->getBody());
        } finally {
            $this->app->instance('path.public', $original);
            unlink($fixture.'/images/escape.png');
            unlink($fixture.'/images/not-an-image.png');
            unlink($fixture.'/images/safe.png');
            unlink($outside);
            rmdir($fixture.'/images'); rmdir($fixture);
        }
    }

    public function test_other_email_previews_embed_the_shared_logo_and_social_icons()
    {
        $this->local();
        foreach (app(AutomaticEmails::class)->all()->keys()->reject(function ($key) { return strpos($key, 'account-welcome') === 0; }) as $key) {
            $sample = app(AutomaticEmails::class)->render($key);
            $this->assertStringContainsString('src="http://pianolit.test/images/brand/app-icon.png', $sample['html']);
            $mail = (new EmailPreview($sample['subject'], $sample['html'], $sample['text']))->attachData('Example attachment', 'example.txt');
            $message = $this->deliver($mail);
            $filenames = array_map(function ($image) { return $image->getFilename(); }, $this->images($message));
            foreach (['app-icon.png', 'youtube.png', 'reddit.png', 'instagram.png'] as $filename) $this->assertContains($filename, $filenames, $key);
            if ($key === 'piece-shared') $this->assertContains('gift-white.png', $filenames);
            $attachments = array_values(array_filter($message->getChildren(), function ($part) { return $part instanceof \Swift_Attachment && !($part instanceof \Swift_Image); }));
            $this->assertCount(1, $attachments);
            $this->assertSame('Example attachment', $attachments[0]->getBody());
        }
    }
}
