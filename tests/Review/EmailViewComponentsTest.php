<?php

namespace Tests\Review;

use App\Mail\{AccountWelcome, AutomaticEmailSettings, IosAccountWelcome, WebsiteAccountWelcome};
use App\Services\Admin\AutomaticEmails;
use Illuminate\Mail\{Mailer, Markdown};
use Illuminate\Mail\Transport\ArrayTransport;

class EmailViewComponentsTest extends ReviewTestCase
{
    public function test_previous_cached_component_paths_do_not_break_production_previews_or_delivery()
    {
        $originalEnvironment = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            config(['app.url' => 'https://pianolit.com']);
            $transport = new ArrayTransport;
            $mailer = new Mailer('email-view-test', $this->app['view'], new \Swift_Mailer($transport), $this->app['events']);
            $mailer->alwaysFrom('preview@example.com', 'PianoLIT');

            foreach ([[resource_path('views/vendor/mail')], ['/previous-release/resources/views/vendor/mail']] as $cachedPaths) {
                // Reproduce a deployed config cache from before the components moved.
                config(['mail.markdown.paths' => $cachedPaths]);
                $this->app->forgetInstance(Markdown::class);
                foreach (['account-welcome-website' => WebsiteAccountWelcome::class, 'account-welcome' => AccountWelcome::class, 'account-welcome-ios' => IosAccountWelcome::class] as $key => $class) {
                    $sample = app(AutomaticEmails::class)->render($key);
                    $this->assertStringContainsString('Welcome, Jamie', $sample['html']);
                    $this->assertStringContainsString('Welcome, Jamie', $sample['text']);
                    $this->assertStringContainsString('width: 130px', $sample['html']);
                    $this->assertStringNotContainsString('<table', $sample['text']);

                    app(AutomaticEmailSettings::class)->setEnabled($key, true, null);
                    (new $class('Jamie'))->to('jamie@example.com')->send($mailer);
                    $this->assertStringContainsString('Welcome, Jamie', $transport->messages()->last()->getBody());
                }
                $legacy = app(AutomaticEmails::class)->render('membership-confirmation');
                $this->assertStringContainsString('You’re all set!', $legacy['html']);
                $this->assertStringNotContainsString('welcome-wrapper', $legacy['html']);
            }
            $this->assertCount(6, $transport->messages());
        } finally {
            $this->app['env'] = $originalEnvironment;
        }
    }

    public function test_configured_custom_components_still_override_the_legacy_fallback()
    {
        $directory = sys_get_temp_dir().'/pianolit-mail-components-'.bin2hex(random_bytes(6));
        mkdir($directory.'/html', 0700, true);
        $file = $directory.'/html/message.blade.php';
        file_put_contents($file, "Custom email branding\n".file_get_contents(resource_path('views/vendor/mail/html/message.blade.php')));
        config(['mail.markdown.paths' => [$directory]]);
        $this->app->forgetInstance(Markdown::class);
        try {
            $sample = app(AutomaticEmails::class)->render('membership-confirmation');
            $this->assertStringContainsString('Custom email branding', $sample['html']);
            $this->assertStringContainsString('You’re all set!', $sample['text']);
            $this->assertStringNotContainsString('welcome-wrapper', $sample['html']);
        } finally {
            unlink($file); rmdir($directory.'/html'); rmdir($directory);
        }
    }
}
