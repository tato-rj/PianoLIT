<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece, Tag};
use App\Services\Pieces\ScoreSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Artisan, Storage};

class PieceScoreSourceTest extends ReviewTestCase
{
    public $mockConsoleOutput = false;

    public function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function piece(string $name, array $score = []): Piece
    {
        return Model::withoutEvents(function () use ($name, $score) {
            return create(Piece::class, $score + ['name' => $name, 'composer_id' => create(Composer::class)->id]);
        });
    }

    private function pdf(string $label, int $y = 25, bool $collection = false): string
    {
        $pdf = new \Cpdf([0, 0, 612, 792], true, sys_get_temp_dir(), sys_get_temp_dir());
        $pdf->selectFont(resource_path('fonts/escore/LibreBodoni-Regular'));
        if ($collection) {
            $pdf->addInfo('Creator', 'PianoLIT eScore');
        }
        $pdf->addText(270, $y, 11, $label);
        return $pdf->output();
    }

    private function requirePoppler(): void
    {
        if (!app(ScoreSource::class)->executable()) {
            $this->markTestSkipped('Poppler pdftotext is required for PDF inspection.');
        }
    }

    public function test_backfill_previews_then_marks_only_confirmed_matches_and_respects_edits()
    {
        $this->requirePoppler();
        $disk = Storage::disk('public');
        $disk->put('app/score/pianolit-branded.pdf', $this->pdf('PianoLIT eScore'));
        $disk->put('app/score/pianolit-unbranded.pdf', $this->pdf('Downloaded edition'));
        $disk->put('app/score/pianolit-body-text.pdf', $this->pdf('PianoLIT eScore', 600));
        $disk->put('app/score/pianolit-generated.pdf', $this->pdf('PianoLIT eScore', 25, true));
        $disk->put('references/export.pdf', $this->pdf('Verified unbranded export'));
        $disk->copy('references/export.pdf', 'app/score/reference-match.pdf');

        $branded = $this->piece('Branded', ['score_path' => 'app/score/pianolit-branded.pdf']);
        $manual = $this->piece('Reviewed', ['score_path' => $branded->score_path, 'score_source' => 'external']);
        $manualUnknown = $this->piece('Uncertain', ['score_path' => $branded->score_path, 'score_source' => 'unknown']);
        $reference = $this->piece('Reference match', ['score_path' => 'app/score/reference-match.pdf']);
        $unresolved = collect(['unbranded', 'body-text', 'generated', 'missing'])->map(function ($file) {
            return $this->piece(ucfirst($file), ['score_path' => "app/score/pianolit-$file.pdf"]);
        });
        $this->piece('No PDF');
        $options = ['--reference-dir' => $disk->path('references')];

        $this->assertSame(0, Artisan::call('pieces:backfill-score-sources', $options));
        $this->assertStringContainsString('Matched: 2. Unknown: 4. Saved: 0.', Artisan::output());
        $this->assertNull($branded->fresh()->score_source);
        $this->assertSame(0, Artisan::call('pieces:backfill-score-sources', $options + ['--apply' => true]));
        $this->assertStringContainsString('Matched: 2. Unknown: 4. Saved: 2.', Artisan::output());
        $this->assertSame('pianolit', $branded->fresh()->score_source);
        $this->assertSame('pianolit', $reference->fresh()->score_source);
        $this->assertSame('external', $manual->fresh()->score_source);
        $this->assertSame('unknown', $manualUnknown->fresh()->score_source);
        foreach ($unresolved as $piece) {
            $this->assertNull($piece->fresh()->score_source);
        }
        $this->assertSame(0, Artisan::call('pieces:backfill-score-sources', $options + ['--apply' => true]));
        $this->assertStringContainsString('Matched: 0. Unknown: 4. Saved: 0.', Artisan::output());
    }

    public function test_score_source_is_read_only_in_the_directory_and_absent_from_the_editor_form()
    {
        $piece = $this->piece('Branded piece', ['score_path' => 'scores/edition.pdf', 'score_source' => 'pianolit']);
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $response = $this->get(route('admin.pieces.index', ['filter_score_source' => 'pianolit']))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $row = $xpath->query('//button[@data-freepick-piece="'.$piece->id.'"]/ancestor::tr')->item(0);
        $this->assertStringContainsString('PianoLIT eScore', $row->textContent);
        $this->assertCount(0, $xpath->query('.//form', $row));
        $this->get(route('admin.pieces.edit', $piece))->assertOk()->assertSee('name="score"', false)
            ->assertDontSee('Edit score source')->assertDontSee('name="score_source"', false);

        $serialized = $piece->setAppends([])->attributesToArray();
        $this->assertArrayNotHasKey('score_source', $serialized);
        $this->assertArrayNotHasKey('score_source_url', $serialized);
    }

    public function test_creating_and_editing_pieces_detect_score_source_from_the_uploaded_pdf()
    {
        $this->requirePoppler();
        $this->actingAs(create(Admin::class, ['role' => 'manager']), 'admin');
        $disk = Storage::disk('public');
        $payload = ['name' => 'Upload a score', 'key' => 'C major', 'composer_id' => create(Composer::class)->id, 'is_public' => 'on'];
        foreach (['period' => 'classical', 'length' => 'short', 'level' => 'elementary'] as $type => $name) {
            $payload[$type] = [create(Tag::class, compact('type', 'name'))->id];
        }
        Model::withoutEvents(function () use ($payload) {
            $this->post(route('admin.pieces.store'), $payload + [
                'score' => UploadedFile::fake()->createWithContent('original.pdf', $this->pdf('PianoLIT eScore')),
            ])->assertRedirect()->assertSessionHas('status');
        });
        $piece = Piece::where('name', $payload['name'])->firstOrFail();
        $disk->assertExists($piece->score_path);
        $this->assertSame('pianolit', $piece->score_source);

        $oldPath = $piece->score_path;
        $piece->updateQuietly(['score_source_url' => 'https://example.test/old']);
        $this->post(route('admin.pieces.update', $piece), $payload + [
            '_method' => 'PATCH', 'score' => UploadedFile::fake()->createWithContent('replacement.pdf', $this->pdf('Other edition')),
            'score_source' => 'pianolit', 'score_source_url' => 'https://example.test/forged',
        ])->assertRedirect()->assertSessionHas('status');
        $piece->refresh();
        $disk->assertMissing($oldPath);
        $disk->assertExists($piece->score_path);
        $this->assertNull($piece->score_source);
        $this->assertNull($piece->score_source_url);

        $this->post(route('admin.pieces.update', $piece), $payload + [
            '_method' => 'PATCH', 'score' => UploadedFile::fake()->createWithContent('branded.pdf', $this->pdf('PianoLIT eScore')),
        ])->assertRedirect()->assertSessionHas('status');
        $piece->refresh();
        $this->assertSame('pianolit', $piece->score_source);

        $oldPath = $piece->score_path;
        $this->delete(route('admin.pieces.destroy-file', $piece), ['method' => 'deleteScore'])->assertRedirect();
        $piece->refresh();
        $disk->assertMissing($oldPath);
        $this->assertNull($piece->score_path);
        $this->assertNull($piece->score_source);
    }
}
