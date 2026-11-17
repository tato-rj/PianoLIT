<?php

namespace Tests\Review;

use App\{Admin, Composer, Piece};
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

    public function test_source_controls_preserve_filters_validate_links_and_reject_stale_and_unauthorized_writes()
    {
        $piece = $this->piece('Branded piece', ['score_path' => 'scores/edition.pdf']);
        $manager = create(Admin::class, ['role' => 'manager']);
        $this->actingAs($manager, 'admin');
        $index = route('admin.pieces.index', ['filter_score_source' => 'unknown']);
        $editor = route('admin.pieces.edit', $piece);
        $source = route('admin.pieces.update-score-source', $piece);
        $version = hash('sha256', $piece->score_path);
        $this->get($index)->assertOk()->assertSee('Branded piece')->assertSee('table-score-source-'.$piece->id);
        $this->get($editor)->assertOk()->assertSee('Source link (optional)');

        $this->patch($source.'?filter_score_source=unknown', ['score_source' => 'pianolit', 'score_version' => $version])
            ->assertRedirect($index);
        $this->assertSame('pianolit', $piece->fresh()->score_source);
        $this->get($index)->assertOk()->assertDontSee('Branded piece');

        $this->patch($source, ['score_source' => 'external', 'score_source_url' => 'https://example.test/edition',
            'score_version' => $version, 'return_to_editor' => 1])->assertRedirect($editor.'#piece-score-source');
        $this->assertSame('https://example.test/edition', $piece->fresh()->score_source_url);
        $serialized = $piece->fresh()->setAppends([])->attributesToArray();
        $this->assertArrayNotHasKey('score_source', $serialized);
        $this->assertArrayNotHasKey('score_source_url', $serialized);

        $this->withExceptionHandling()->from($editor)->patch($source, ['score_source' => 'external',
            'score_source_url' => 'ftp://example.test/edition', 'score_version' => $version])->assertSessionHasErrors('score_source_url', null, 'scoreSource'.$piece->id);
        $piece->update(['score_path' => 'scores/replacement.pdf']);
        $this->withExceptionHandling()->from($editor)->patch($source, ['score_source' => 'pianolit', 'score_version' => $version])
            ->assertSessionHasErrors('score_source', null, 'scoreSource'.$piece->id);
        $this->assertSame('external', $piece->fresh()->score_source);

        $this->actingAs(create(Admin::class, ['role' => 'editor']), 'admin')->patch($source, ['score_source' => 'pianolit',
            'score_version' => hash('sha256', 'scores/replacement.pdf')])->assertForbidden();
    }

    public function test_replacing_and_deleting_a_pdf_reset_its_source_and_new_uploads_are_detected()
    {
        $this->requirePoppler();
        $piece = $this->piece('Replace score', ['score_path' => 'app/score/old.pdf', 'score_source' => 'external',
            'score_source_url' => 'https://example.test/edition']);
        $disk = Storage::disk('public');
        $disk->put($piece->score_path, 'old PDF');
        $piece->saveScore(UploadedFile::fake()->createWithContent('original.pdf', $this->pdf('PianoLIT eScore')));
        $disk->assertMissing('app/score/old.pdf');
        $disk->assertExists($piece->score_path);
        $this->assertSame('pianolit', $piece->fresh()->score_source);
        $this->assertNull($piece->fresh()->score_source_url);

        $oldPath = $piece->score_path;
        $piece->saveScore(UploadedFile::fake()->createWithContent('next.pdf', $this->pdf('Other edition')));
        $disk->assertMissing($oldPath);
        $this->assertNull($piece->fresh()->score_source);

        $oldPath = $piece->score_path;
        $piece->update(['score_source' => 'external', 'score_source_url' => 'https://example.test/other']);
        $piece->deleteScore();
        $disk->assertMissing($oldPath);
        $this->assertNull($piece->fresh()->score_path);
        $this->assertNull($piece->fresh()->score_source);
        $this->assertNull($piece->fresh()->score_source_url);
    }
}
