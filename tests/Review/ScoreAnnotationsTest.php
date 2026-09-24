<?php

namespace Tests\Review;

use App\{Piece, User};
use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ScoreAnnotationsTest extends ReviewTestCase
{
    protected $piece, $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->withExceptionHandling();
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        Model::withoutEvents(function () {
            $this->piece = create(Piece::class, ['score_path' => 'scores/one.pdf', 'score_url' => null]);
            $this->user = create(User::class, ['super_user' => true]);
        });
    }

    protected function url($piece = null, $params = [])
    {
        return route('webapp.pieces.score.annotations.show', array_merge(['piece' => $piece ?? $this->piece], $params));
    }

    protected function payload($marks = null, $revision = 0)
    {
        return [
            'score' => hash('sha256', $this->piece->score_path),
            'fingerprint' => str_repeat('a', 32), 'revision' => $revision,
            'marks' => $marks ?? [
                ['id' => 'fingering1', 'type' => 'text', 'page' => 1, 'color' => '#1565c0', 'x' => .25, 'y' => .3, 'size' => .03, 'text' => '1 2 3'],
                ['id' => 'pen1', 'type' => 'stroke', 'page' => 2, 'color' => '#20252b', 'width' => .004, 'points' => [['x' => .2, 'y' => .3], ['x' => .25, 'y' => .35]]],
            ],
        ];
    }

    public function test_markings_persist_for_only_the_session_user_and_specific_piece()
    {
        $this->actingAs($this->user, 'web');
        $data = $this->payload();
        $data['user_id'] = 999; $data['piece_id'] = 999;
        $this->putJson($this->url(), $data)->assertOk()->assertJsonPath('revision', 1);
        $this->getJson($this->url(null, $this->payload([])))->assertOk()
            ->assertJsonPath('marks.0.text', '1 2 3')->assertJsonPath('marks.1.page', 2)
            ->assertHeader('Cache-Control', 'no-store, private');
        $otherPiece = Model::withoutEvents(function () { return create(Piece::class, ['score_path' => $this->piece->score_path, 'score_url' => null]); });
        $this->getJson($this->url($otherPiece, $this->payload([])))->assertOk()->assertExactJson(['revision' => 0, 'marks' => []]);
        $otherUser = Model::withoutEvents(function () { return create(User::class, ['super_user' => true]); });
        $this->actingAs($otherUser, 'web');
        $this->getJson($this->url(null, $data))->assertOk()->assertExactJson(['revision' => 0, 'marks' => []]);
        $this->putJson($this->url(), $this->payload([]))->assertOk();
        $this->actingAs($this->user, 'web');
        $this->getJson($this->url(null, $this->payload([])))->assertJsonPath('marks.0.text', '1 2 3');
        $this->assertDatabaseHas('score_annotations', ['user_id' => $this->user->id, 'piece_id' => $this->piece->id]);
        $this->assertDatabaseCount('score_annotations', 2);
    }

    public function test_only_full_access_accounts_can_read_or_write_annotations()
    {
        $this->getJson($this->url(null, $this->payload([])))->assertUnauthorized();
        $this->putJson($this->url(), $this->payload())->assertUnauthorized();
        $this->user->updateQuietly(['super_user' => false]);
        $this->actingAs($this->user, 'web');
        $this->getJson($this->url(null, $this->payload([])))->assertForbidden();
        $this->putJson($this->url(), $this->payload())->assertForbidden();
        $source = Model::withoutEvents(function () {
            $source = create(Stripe::class, ['status' => 'trialing', 'renews_at' => now()->addDays(7)]);
            Membership::create(['user_id' => $this->user->id, 'source_type' => Stripe::class, 'source_id' => $source->id]);
            return $source;
        });
        $this->user->unsetRelation('membership');
        $this->putJson($this->url(), $this->payload())->assertOk();
        $this->user->membership->source->updateQuietly(['ended_at' => now()]);
        $this->getJson($this->url(null, $this->payload([])))->assertForbidden();
        $this->putJson($this->url(), $this->payload([], 1))->assertForbidden();
        $this->user->updateQuietly(['super_user' => true]);
        $this->getJson($this->url(null, $this->payload([])))->assertOk()->assertJsonPath('marks.0.text', '1 2 3');
    }

    public function test_pdf_replacements_and_other_fingerprints_do_not_reuse_markings()
    {
        $this->actingAs($this->user, 'web');
        $data = $this->payload();
        $this->putJson($this->url(), $data)->assertOk();
        $other = array_merge($data, ['fingerprint' => str_repeat('b', 32)]);
        $this->getJson($this->url(null, $other))->assertOk()->assertJsonPath('marks', []);
        $this->piece->updateQuietly(['score_path' => 'scores/replacement.pdf']);
        $this->getJson($this->url(null, $data))->assertStatus(409);
        $this->putJson($this->url(), $data)->assertStatus(409);
        $this->getJson($this->url(null, $this->payload([])))->assertOk()->assertJsonPath('marks', []);
        $this->assertDatabaseCount('score_annotations', 1);
    }

    public function test_retries_are_idempotent_and_stale_writes_cannot_overwrite_newer_markings()
    {
        $this->actingAs($this->user, 'web');
        $data = $this->payload();
        $this->putJson($this->url(), $data)->assertOk()->assertJsonPath('revision', 1);
        $this->putJson($this->url(), $data)->assertOk()->assertJsonPath('revision', 1);
        $stale = $data; $stale['marks'][0]['text'] = 'wrong';
        $this->putJson($this->url(), $stale)->assertStatus(409);
        $this->getJson($this->url(null, $data))->assertJsonPath('marks.0.text', '1 2 3');
        DB::enableQueryLog();
        DB::flushQueryLog();
        $this->putJson($this->url(), $this->payload([], 1))->assertOk()->assertJsonPath('revision', 2);
        $queries = collect(DB::getQueryLog())->filter(function ($query) { return strpos($query['query'], 'score_annotations') !== false; });
        DB::disableQueryLog();
        $this->assertCount(1, $queries, 'An acknowledged existing score needs only one annotation UPDATE.');
        $this->putJson($this->url(), $data)->assertStatus(409);
        $this->getJson($this->url(null, $data))->assertExactJson(['revision' => 2, 'marks' => []]);
        $this->assertDatabaseCount('score_annotations', 1);
    }

    public function test_native_picker_colors_persist_for_text_and_pen_marks()
    {
        $this->actingAs($this->user, 'web');
        $data = $this->payload();
        $data['marks'][0]['color'] = '#8a39cf';
        $data['marks'][1]['color'] = '#B87512';
        $this->putJson($this->url(), $data)->assertOk();
        $this->getJson($this->url(null, $data))->assertJsonPath('marks.0.color', '#8a39cf')
            ->assertJsonPath('marks.1.color', '#B87512');
    }

    public function test_stale_first_write_does_not_create_a_row()
    {
        $this->actingAs($this->user, 'web');
        $this->putJson($this->url(), $this->payload(null, 12))->assertStatus(409);
        $this->assertDatabaseCount('score_annotations', 0);
    }

    public function test_failed_initial_save_rolls_back_both_creation_and_update()
    {
        $this->actingAs($this->user, 'web');
        $this->withoutExceptionHandling();
        DB::listen(function ($query) {
            if (strpos($query->sql, 'update "score_annotations"') === 0) {
                throw new \RuntimeException('Simulated database failure');
            }
        });
        try {
            $this->putJson($this->url(), $this->payload());
            $this->fail('The simulated failure must propagate.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated database failure', $exception->getMessage());
        }
        $this->assertDatabaseCount('score_annotations', 0);
    }

    public function test_validation_rejects_invalid_or_excessive_annotation_data()
    {
        $this->actingAs($this->user, 'web');
        foreach ([
            ['marks.0.type', 'html'], ['marks.0.color', 'url(https://example.com)'],
            ['marks.0.color', '#fff'], ['marks.0.color', '#aa0000;'],
            ['marks.0.x', 1.1], ['marks.0.page', 0], ['marks.0.size', .5],
            ['marks.0.text', str_repeat('a', 81)], ['marks.0.unexpected', 'field'],
            ['marks.1.points.0.y', -1], ['marks.1.width', 0], ['marks.1.points', []],
            ['marks.1.points', array_fill(0, 1501, ['x' => .2, 'y' => .2])],
            ['revision', -1], ['fingerprint', '../other.pdf'], ['marks.1.id', 'fingering1'],
        ] as [$key, $value]) {
            $data = $this->payload(); data_set($data, $key, $value);
            $this->putJson($this->url(), $data)->assertStatus(422);
        }
        $data = $this->payload(); unset($data['marks'][0]['text']);
        $this->putJson($this->url(), $data)->assertStatus(422);
        $this->assertDatabaseCount('score_annotations', 0);
    }

    public function test_unavailable_scores_are_rejected_and_deleting_a_piece_cleans_up_markings()
    {
        $this->actingAs($this->user, 'web');
        $this->putJson($this->url(), $this->payload())->assertOk();
        $this->piece->updateQuietly(['score_url' => 'https://example.com/buy']);
        $this->getJson($this->url(null, $this->payload([])))->assertNotFound();
        $this->piece->updateQuietly(['score_url' => null, 'score_path' => null]);
        $this->putJson($this->url(), $this->payload())->assertNotFound();
        DB::table('pieces')->where('id', $this->piece->id)->delete();
        $this->assertDatabaseCount('score_annotations', 0);
    }

    public function test_editor_has_annotation_tools_and_can_export_an_isolated_browser_fixture()
    {
        $html = view('webapp.piece.components.score-editor', ['piece' => $this->piece])->render();
        foreach (['data-tool="pen"', 'data-tool="text"', 'data-tool="erase"', 'data-undo', 'data-redo', 'data-annotations-url', 'type="color"', 'fa-palette'] as $control) {
            $this->assertStringContainsString($control, $html);
        }
        if ($directory = getenv('SCORE_EDITOR_PREVIEW_DIR')) {
            for ($id = 1; $id <= 2; $id++) {
                $preview = preg_replace('/data-pdf-url="[^"]*"/', 'data-pdf-url="/score.pdf"', $html);
                $preview = preg_replace('/data-annotations-url="[^"]*"/', 'data-annotations-url="/annotations/'.$id.'"', $preview);
                $preview = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/app.css"></head><body><main class="container py-4" style="max-width:900px"><h3>Score '.$id.'</h3><p><a href="/1.html">Score 1</a> &middot; <a href="/2.html">Score 2</a></p>'.$preview.'<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.min.js"></script><script src="/axios.js"></script><script src="/score-editor.js"></script><script>new ScoreEditor.Editor(document.getElementById("score-editor"), pdfjsLib, axios);</script></main></body></html>';
                file_put_contents($directory.'/'.$id.'.html', $preview);
            }
        }
    }

}
