<?php

namespace Tests\Review;

use App\{Admin, Piece, Tag, User};
use App\Support\LinkShare;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Mail, Notification, Redis};

class LinkShareTest extends ReviewTestCase
{
    public function test_destination_links_preserve_unicode_titles_and_encode_complete_urls()
    {
        $url = 'https://my.pianolit.com/pieces/12?part=a&edition=1';
        $share = LinkShare::forPage($url, "Prélude & Fugue\r\nChopin");
        $links = collect($share['destinations'])->keyBy('label');
        $this->assertSame(['Email', 'Text', 'WhatsApp', 'Facebook', 'Reddit', 'Telegram'], $links->keys()->all());
        foreach ($links as $label => $link) {
            parse_str(parse_url($link['url'], PHP_URL_QUERY), $query);
            if ($label === 'Email') {
                $this->assertSame('Prélude & Fugue  Chopin', $query['subject']);
                $this->assertSame($share['message'], $query['body']);
            } elseif (in_array($label, ['Facebook', 'Reddit', 'Telegram'])) {
                $this->assertSame($url, $query[$label === 'Facebook' ? 'u' : 'url']);
            } else {
                $this->assertSame($share['message'], $query[$label === 'Text' ? 'body' : 'text']);
            }
        }
    }

    public function test_guests_and_accounts_get_the_same_clean_link_dialog_beside_favorites()
    {
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        [$piece, $user] = Model::withoutEvents(function () {
            $piece = create(Piece::class, ['name' => 'A piece to share']);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'] as $type => $name) {
                $piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            return [$piece, create(User::class)];
        });
        foreach ([false, true] as $signedIn) {
            if ($signedIn) $this->actingAs($user, 'web');
            $html = $this->withSession(['_old_input' => ['share_url' => 'https://unrelated.example/']])
                ->get(route('webapp.pieces.show', $piece).'?utm_source=example&return_token=private')->assertOk()->getContent();
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $this->assertCount(1, $xpath->query('//button[@aria-label="Share this piece" and @data-bs-target="#piece-share-modal"]'));
            $this->assertCount(0, $xpath->query('//*[@id="options-panel"]//*[@data-bs-target="#piece-share-modal"]'));
            $this->assertCount(1, $xpath->query('//*[@id="piece-share-modal"]'));
            $field = $xpath->query('//*[@id="piece-share-modal"]//input[@readonly]')->item(0);
            $this->assertSame(route('webapp.pieces.show', $piece), $field->getAttribute('value'));
            $this->assertCount(6, $xpath->query('//*[@id="piece-share-modal"]//a[starts-with(@aria-label,"Share via ")]'));
            $this->assertCount(0, $xpath->query('//*[@id="piece-share-modal"]//form | //*[@name="recipient_email"]'));
            foreach ($xpath->query('//*[@id="piece-share-modal"]//a[@target="_blank"]') as $link) {
                $this->assertSame('noopener noreferrer', $link->getAttribute('rel'));
            }
        }
        Mail::assertNothingOutgoing();
    }

    public function test_pianolit_cannot_send_piece_share_emails_or_enable_the_retired_key()
    {
        $this->withExceptionHandling();
        [$user, $admin] = Model::withoutEvents(function () { return [create(User::class), create(Admin::class)]; });
        $this->actingAs($user, 'web')->actingAs($admin, 'admin');
        Notification::fake();
        $this->postJson('http://my.localhost/pieces/1/share', ['recipient_email' => 'friend@example.test'])
            ->assertRedirect(route('webapp.discover'));
        $this->getJson(route('admin.emails.preview', 'piece-shared'))->assertNotFound();
        $this->getJson(route('admin.emails.content', 'piece-shared'))->assertNotFound();
        $this->patchJson(route('admin.emails.sending', 'piece-shared'), ['enabled' => true])->assertNotFound();
        $this->postJson(route('admin.emails.send'), ['email_key' => 'piece-shared', 'email' => 'friend@example.test'])->assertUnprocessable();
        Mail::assertNothingOutgoing();
        Notification::assertNothingSent();
    }

    public function test_share_links_cannot_use_non_web_schemes_or_embedded_credentials()
    {
        foreach (['javascript:alert(1)', '//example.test/path', 'https://user:password@example.test/path'] as $url) {
            try {
                LinkShare::forPage($url, 'Example');
                $this->fail('An invalid sharing URL was accepted.');
            } catch (\InvalidArgumentException $error) {
                $this->assertNotEmpty($error->getMessage());
            }
        }
    }
}
