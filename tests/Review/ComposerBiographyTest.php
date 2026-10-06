<?php

namespace Tests\Review;

use App\{Admin, Composer, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\{Http, Redis};

class ComposerBiographyTest extends ReviewTestCase
{
    private $admin;
    private $composer;

    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        $this->admin = create(Admin::class, ['role' => 'editor']);
        $this->composer = Model::withoutEvents(function () {
            return create(Composer::class, ['creator_id' => $this->admin->id, 'name' => 'Clara Schumann', 'biography' => 'Original bio']);
        });
        $this->actingAs($this->admin, 'admin');
        config(['services.openai.key' => 'fake-review-key', 'services.openai.model' => 'gpt-4o-mini']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->withExceptionHandling();
    }

    private function regenerate(array $data = ['biography' => 'Clara Schumann was a pianist and composer.'])
    {
        return $this->postJson(route('admin.composers.regenerate-biography', $this->composer), $data);
    }

    private function output($paragraphs): array
    {
        return ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [
            ['type' => 'output_text', 'text' => json_encode(['paragraphs' => $paragraphs])],
        ]]]];
    }

    public function test_regeneration_returns_a_draft_without_saving_and_sends_the_current_source_with_server_settings()
    {
        Http::fake(['https://api.openai.com/v1/responses' => Http::response($this->output([
            ' Clara Schumann was a pianist and composer. ', 'She wrote music for the piano.', 'Her music is still played today.',
        ]))]);
        $this->regenerate(['biography' => 'Edited source', 'model' => 'forged-model', 'user_id' => 123])
            ->assertOk()->assertExactJson(['biography' => "Clara Schumann was a pianist and composer.\n\nShe wrote music for the piano.\n\nHer music is still played today."]);
        $this->assertSame('Original bio', $this->composer->fresh()->biography);
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            $input = json_decode($request['input'], true);
            return $request->method() === 'POST' && $request->hasHeader('Authorization', 'Bearer fake-review-key')
                && $request['model'] === 'gpt-4o-mini' && $request['store'] === false
                && $input === ['composer' => 'Clara Schumann', 'source_biography' => 'Edited source']
                && $request['text']['format']['schema']['properties']['paragraphs']['maxItems'] === 3
                && strpos($request['instructions'], 'Never use jargon') !== false
                && strpos($request['instructions'], 'do not invent') !== false;
        });
    }

    public function test_web_accounts_and_guests_cannot_regenerate()
    {
        Http::fake();
        auth('admin')->logout();
        $this->regenerate()->assertUnauthorized();
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $this->regenerate()->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_regeneration_requires_the_session_csrf_token()
    {
        $this->app->bind(\App\Http\Middleware\VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        Http::fake(['*' => Http::response($this->output(['A short bio.']))]);
        $this->withSession(['_token' => 'review-session-token']);
        $this->regenerate()->assertStatus(419);
        Http::assertNothingSent();
        $this->withHeader('X-CSRF-TOKEN', 'review-session-token')->regenerate()->assertOk();
    }

    public function test_another_editor_cannot_regenerate_but_a_manager_can()
    {
        Http::fake(['*' => Http::response($this->output(['A short bio.']))]);
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->regenerate()->assertForbidden();
        Http::assertNothingSent();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $this->regenerate()->assertOk();
    }

    public function test_edit_page_shows_button_only_with_update_permission()
    {
        $this->get(route('admin.composers.edit', $this->composer))->assertOk()
            ->assertSee('Regenerate bio')->assertSee(route('admin.composers.regenerate-biography', $this->composer))
            ->assertSee('composer-biography-admin.js')->assertDontSee('fake-review-key');
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        $this->get(route('admin.composers.edit', $this->composer))->assertOk()->assertDontSee('Regenerate bio');
    }

    /** @dataProvider invalidSources */
    public function test_invalid_sources_do_not_call_openai($source)
    {
        Http::fake();
        $this->regenerate(['biography' => $source])->assertUnprocessable()->assertJsonValidationErrors('biography');
        Http::assertNothingSent();
    }

    public static function invalidSources(): array
    {
        return [[''], ['   '], [['nested']], [str_repeat('a', 20001)]];
    }

    public function test_missing_key_fails_clearly_without_a_request()
    {
        Http::fake();
        config(['services.openai.key' => null]);
        $this->regenerate()->assertStatus(503)->assertJsonFragment(['message' => 'Bio regeneration is not configured. Set the server OpenAI API key.']);
        Http::assertNothingSent();
    }

    /** @dataProvider invalidParagraphs */
    public function test_invalid_generated_bios_are_rejected_without_saving($paragraphs)
    {
        Http::fake(['*' => Http::response($this->output($paragraphs))]);
        $this->regenerate()->assertStatus(502);
        $this->assertSame('Original bio', $this->composer->fresh()->biography);
    }

    public static function invalidParagraphs(): array
    {
        return [[[]], [['One.', 'Two.', 'Three.', 'Four.']], [['']], [[123]], [["One.\n\nTwo."]],
            [['<p>Bio</p>']], [[str_repeat('word ', 61)]], [[str_repeat('a', 601)]], [['first' => 'Bio']]];
    }

    /** @dataProvider failedResponses */
    public function test_failed_incomplete_malformed_and_refused_responses_are_safe($body, $status)
    {
        Http::fake(['*' => Http::response($body, $status)]);
        $this->regenerate()->assertStatus(502)->assertDontSee('secret-upstream-text');
        $this->assertSame('Original bio', $this->composer->fresh()->biography);
    }

    public static function failedResponses(): array
    {
        return [[['error' => 'secret-upstream-text'], 401], [['error' => 'secret-upstream-text'], 429],
            [['error' => 'secret-upstream-text'], 500], [['status' => 'incomplete'], 200],
            [['status' => 'completed', 'output' => []], 200],
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'secret-upstream-text']]]]], 200],
            [['status' => 'completed', 'output' => 'bad shape'], 200],
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => 'bad shape']]], 200],
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'output_text', 'text' => ['bad shape']]]]]], 200],
            ['not json', 200]];
    }

    public function test_connection_failure_is_safe_and_rate_limit_bounds_requests()
    {
        Http::fake(function () { throw new ConnectionException('secret-upstream-text'); });
        for ($i = 0; $i < 10; $i++) $this->regenerate()->assertStatus(502)->assertDontSee('secret-upstream-text');
        $this->regenerate()->assertStatus(429);
    }
}
