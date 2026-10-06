<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class AdminTextImproverTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin');
        config(['services.openai.key' => 'fake-review-key', 'services.openai.model' => 'gpt-4o-mini']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        $this->withExceptionHandling();
    }

    private function improve(array $data = ['texts' => ['Current unsaved text']])
    {
        return $this->postJson(route('admin.text.improve'), $data);
    }

    private static function output($texts): array
    {
        return ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [
            ['type' => 'output_text', 'text' => json_encode(['texts' => $texts])],
        ]]]];
    }

    public function test_returns_only_a_draft_with_same_defaults_and_server_configuration()
    {
        Http::fake(['*' => Http::response(self::output(['Clearer text']))]);
        $admin = auth('admin')->user();
        $original = $admin->fresh()->getAttributes();
        $this->improve(['texts' => ['Current unsaved text'], 'model' => 'forged-model', 'user_id' => 44])
            ->assertOk()->assertExactJson(['texts' => ['Clearer text']]);
        $this->assertSame($original, $admin->fresh()->getAttributes());
        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.openai.com/v1/responses'
                && $request['model'] === 'gpt-4o-mini' && $request['store'] === false
                && $request->hasHeader('Authorization', 'Bearer fake-review-key')
                && json_decode($request['input'], true) === ['source_texts' => ['Current unsaved text']]
                && strpos($request['instructions'], 'Keep approximately the same length') !== false
                && strpos($request['instructions'], 'Keep the same tone') !== false
                && strpos($request['instructions'], 'Never use jargon') !== false
                && strpos($request['instructions'], 'Do not research, use outside knowledge, invent information') !== false;
        });
    }

    /** @dataProvider preferences */
    public function test_each_length_and_tone_combination($length, $tone, $lengthText, $toneText)
    {
        Http::fake(['*' => Http::response(self::output(['Improved text']))]);
        $this->improve(['texts' => ['Source'], 'length' => $length, 'tone' => $tone])->assertOk();
        Http::assertSent(function ($request) use ($lengthText, $toneText) {
            return strpos($request['instructions'], $lengthText) !== false
                && strpos($request['instructions'], $toneText) !== false;
        });
    }

    public static function preferences(): array
    {
        $data = [];
        foreach (['shorter' => 'more concise', 'same' => 'same length', 'longer' => 'Expand the wording moderately'] as $length => $lengthText) {
            foreach (['casual' => 'conversational tone', 'same' => 'same tone', 'formal' => 'never make it stiff or artificial'] as $tone => $toneText) {
                $data[] = [$length, $tone, $lengthText, $toneText];
            }
        }
        return $data;
    }

    public function test_rich_text_segments_remain_in_order_and_character_limit_is_enforced()
    {
        Http::fake(['*' => Http::response(self::output(['Clearer ', 'linked text', ' follows.']))]);
        $this->improve(['texts' => ['Original ', 'linked text', ' follows.'], 'max_length' => 40])
            ->assertOk()->assertExactJson(['texts' => ['Clearer ', 'linked text', ' follows.']]);
        Http::assertSent(function ($request) {
            $schema = $request['text']['format']['schema']['properties']['texts'];
            return $schema['minItems'] === 3 && $schema['maxItems'] === 3
                && $schema['items']['maxLength'] === 40;
        });
        $this->improve(['texts' => ['Source'], 'max_length' => 3])->assertStatus(502);
    }

    public function test_only_admin_sessions_with_csrf_can_request_rewrites()
    {
        Http::fake(['*' => Http::response(self::output(['Draft']))]);
        $admin = auth('admin')->user();
        auth('admin')->logout();
        $this->improve()->assertUnauthorized();
        $this->actingAs(Model::withoutEvents(function () { return create(User::class); }), 'web');
        $this->improve()->assertUnauthorized();
        Http::assertNothingSent();
        $this->actingAs($admin, 'admin');
        $this->app->bind(\App\Http\Middleware\VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        $this->withSession(['_token' => 'review-session-token']);
        $this->improve()->assertStatus(419);
        Http::assertNothingSent();
        $this->withHeader('X-CSRF-TOKEN', 'review-session-token')->improve()->assertOk();
    }

    /** @dataProvider invalidRequests */
    public function test_invalid_requests_never_call_the_api($data)
    {
        Http::fake();
        $this->improve($data)->assertUnprocessable();
        Http::assertNothingSent();
    }

    public static function invalidRequests(): array
    {
        return [[[]], [['texts' => []]], [['texts' => 'text']], [['texts' => [' ']]], [['texts' => [[1]]]],
            [['texts' => ['wrong' => 'Source']]], [['texts' => [str_repeat('x', 50001)]]],
            [['texts' => [str_repeat('x', 30000), str_repeat('x', 30000)]]],
            [['texts' => ['Source'], 'length' => 'invalid']], [['texts' => ['Source'], 'tone' => ['same']]],
            [['texts' => ['Source'], 'max_length' => 0]], [['texts' => ['Source'], 'max_length' => 50001]]];
    }

    /** @dataProvider badOutputs */
    public function test_malformed_incomplete_refused_and_upstream_failures_are_safe($body, $status)
    {
        Http::fake(['*' => Http::response($body, $status)]);
        $this->improve()->assertStatus(502)->assertDontSee('secret-upstream-text');
    }

    public static function badOutputs(): array
    {
        return [[self::output([]), 200], [self::output(['One', 'Two']), 200], [self::output(['']), 200],
            [self::output([5]), 200], [self::output(['named' => 'Draft']), 200],
            [self::output([str_repeat('x', 50001)]), 200], ['not-json', 200],
            [['status' => 'completed', 'output' => 'wrong'], 200],
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => 'wrong']]], 200],
            [['status' => 'completed', 'output' => [['type' => 'message', 'content' => [['type' => 'refusal', 'refusal' => 'secret-upstream-text']]]]], 200],
            [['status' => 'incomplete', 'incomplete_details' => ['reason' => 'max_output_tokens']], 200],
            [['status' => 'incomplete', 'incomplete_details' => ['reason' => 'secret-upstream-text']], 200],
            [['error' => 'secret-upstream-text'], 401], [['error' => 'secret-upstream-text'], 429],
            [['error' => 'secret-upstream-text'], 500]];
    }

    public function test_missing_key_and_invalid_budget_fail_before_http()
    {
        Http::fake();
        config(['services.openai.key' => null]);
        $this->improve()->assertStatus(503);
        config(['services.openai.key' => 'fake-review-key', 'services.openai.max_output_tokens' => 'bad']);
        $this->improve()->assertStatus(503);
        Http::assertNothingSent();
    }

    public function test_connection_failure_and_request_throttle()
    {
        Http::fake(function () { throw new ConnectionException('secret-upstream-text'); });
        for ($i = 0; $i < 10; $i++) $this->improve()->assertStatus(502)->assertDontSee('secret-upstream-text');
        $this->improve()->assertStatus(429);
    }
}
