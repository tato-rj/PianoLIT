<?php

namespace Tests\Review;

use App\{Admin, Piece, Tag, Tutorial, User};
use App\Services\WebApp\PieceCards;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Http, Mail, Redis};

class PerformanceRetirementTest extends ReviewTestCase
{
    public function test_submission_review_clap_and_processing_endpoints_are_closed()
    {
        $this->withExceptionHandling();
        [$user, $admin] = Model::withoutEvents(function () {
            return [create(User::class), create(Admin::class)];
        });
        $this->actingAs($user, 'web')->actingAs($admin, 'admin');
        foreach ([
            ['GET', 'http://my.localhost/users/performances/1/upload-url'],
            ['POST', 'http://my.localhost/users/performances/1'],
            ['DELETE', 'http://my.localhost/users/performances/1'],
            ['POST', 'http://my.localhost/users/performances/1/clap'],
            ['POST', 'http://localhost/api/users/performances/1/clap'],
            ['GET', 'http://admin.localhost/stage'],
            ['PATCH', 'http://admin.localhost/users/performances/1'],
            ['PATCH', 'http://admin.localhost/users/performances/1/status'],
            ['DELETE', 'http://admin.localhost/users/performances/1'],
            ['POST', 'http://localhost/webhooks/file-manager'],
            ['DELETE', 'http://localhost/webhooks/file-manager'],
        ] as [$method, $url]) {
            $response = $this->json($method, $url, ['user_id' => $user->id]);
            // Missing web-app pages use its existing Discover redirect.
            if (parse_url($url, PHP_URL_HOST) === 'my.localhost') {
                $response->assertRedirect(route('webapp.discover'));
            } else {
                $response->assertNotFound();
            }
        }
        Http::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public function test_published_piece_media_still_renders_without_submission_queries_or_upload_scripts()
    {
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Redis::shouldReceive('get')->andReturn(null);
        [$user, $piece] = Model::withoutEvents(function () {
            $piece = create(Piece::class, ['name' => 'Published recording', 'score_path' => 'example.pdf']);
            foreach (['level' => 'elementary', 'period' => 'baroque', 'length' => 'short', 'mood' => 'happy'] as $type => $name) {
                $piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
            create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Performance', 'category' => 'performance']);
            create(Tutorial::class, ['piece_id' => $piece->id, 'type' => 'Synthesia', 'category' => 'synthesia']);
            return [create(User::class), $piece];
        });
        $this->actingAs($user, 'web');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $response = $this->get(route('webapp.pieces.show', $piece))->assertOk();
        foreach (['piece-performance', 'tab-synthesia', 'tab-score', 'plyr.js'] as $media) $response->assertSee($media, false);
        foreach (['resumable', 'upload-url', 'confirm-performance', 'create-performance', 'claps-count', 'Share my performance', 'stage%20demo'] as $retired) {
            $response->assertDontSee($retired, false);
        }
        $cards = PieceCards::load(collect([$piece]), false);
        $this->assertTrue((bool) $cards->first()->webapp_has_video);
        $this->assertTrue((bool) $cards->first()->webapp_has_synthesia);
        foreach (DB::getQueryLog() as $query) {
            $this->assertDoesNotMatchRegularExpression('/\b(performances|claps)\b/i', $query['query']);
        }
        DB::disableQueryLog();
        Http::assertNothingSent();
        Mail::assertNothingOutgoing();
    }

    public function test_retired_email_keys_cannot_be_previewed_sent_or_enabled_even_with_saved_settings()
    {
        $this->withExceptionHandling();
        $admin = create(Admin::class);
        $this->actingAs($admin, 'admin');
        foreach (['performance-submitted', 'performance-approved'] as $key) {
            DB::table('automatic_email_settings')->insert(['email_key' => $key, 'enabled' => true]);
            $this->getJson(route('admin.emails.preview', $key))->assertNotFound();
            $this->getJson(route('admin.emails.content', $key))->assertNotFound();
            $this->patchJson(route('admin.emails.sending', $key), ['enabled' => true])->assertNotFound();
            $this->postJson(route('admin.emails.send'), ['email_key' => $key, 'email' => 'preview@example.test'])->assertUnprocessable();
        }
        $this->get(route('admin.emails.index'))->assertOk()->assertDontSee('Performance submitted')->assertDontSee('Performance approved')
            ->assertDontSee('automatic-emails-performances')->assertSee('Synthesia request received')->assertSee('Membership confirmation');
        Mail::assertNothingOutgoing();
    }
}
