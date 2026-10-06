<?php

namespace Tests\Review;

use App\Composer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\{Artisan, DB, Http, Storage};

class RegenerateComposerBiographiesTest extends ReviewTestCase
{
    public $mockConsoleOutput = false;
    private $output;

    public function setUp(): void
    {
        parent::setUp();
        config(['services.openai.key' => 'fake-review-key', 'services.openai.model' => 'gpt-4.1-mini']);
        Http::swap(new \Illuminate\Http\Client\Factory);
        Storage::fake('local');
    }

    private function composer(string $source = 'Original source'): Composer
    {
        return Model::withoutEvents(function () use ($source) {
            return create(Composer::class, ['biography' => $source, 'country_id' => null, 'creator_id' => null]);
        });
    }

    private function response(): array
    {
        return ['status' => 'completed', 'output' => [['type' => 'message', 'content' => [
            ['type' => 'output_text', 'text' => json_encode(['paragraphs' => ['Early life.', 'Music.', 'Later life.']])],
        ]]]];
    }

    private function runCommand(array $options = []): int
    {
        $status = Artisan::call('composers:regenerate-bios', $options);
        $this->output = Artisan::output();
        return $status;
    }

    private function backup(): array
    {
        $files = Storage::disk('local')->files('composer-bio-backups');
        $this->assertCount(1, $files);
        $this->assertSame('private', Storage::disk('local')->getVisibility($files[0]));
        return array_map(function ($line) { return json_decode($line, true); },
            array_filter(explode("\n", Storage::disk('local')->get($files[0]))));
    }

    public function test_all_bios_are_rewritten_once_saved_and_backed_up_without_changing_other_fields()
    {
        $first = $this->composer('First current biography.');
        $second = $this->composer('Second current biography.');
        $original = $first->fresh()->getAttributes();
        Http::fake(['*' => Http::response($this->response())]);

        $this->assertSame(0, $this->runCommand());
        foreach ([$first, $second] as $composer) {
            $this->assertSame("Early life.\n\nMusic.\n\nLater life.", $composer->fresh()->biography);
        }
        foreach ($original as $field => $value) {
            if (! in_array($field, ['biography', 'updated_at'])) {
                $this->assertSame($value, $first->fresh()->getRawOriginal($field), $field);
            }
        }
        Http::assertSentCount(2);
        foreach ([$first, $second] as $composer) {
            Http::assertSent(function ($request) use ($composer) {
                return $request['model'] === 'gpt-4.1-mini'
                    && json_decode($request['input'], true) === ['composer' => $composer->name, 'source_biography' => $composer->biography]
                    && $request['text']['format']['schema']['properties']['paragraphs']['minItems'] === 3;
            });
        }
        $backup = $this->backup();
        $this->assertSame([$first->id, $second->id], array_column($backup, 'id'));
        $this->assertSame(['First current biography.', 'Second current biography.'], array_column($backup, 'original_biography'));
        $this->assertSame($first->fresh()->biography, $backup[0]['generated_biography']);
        $this->assertStringContainsString('2 saved, 0 empty bios skipped, 0 failed', $this->output);
    }

    public function test_empty_bios_are_skipped_and_oversized_sources_fail_without_api_calls()
    {
        $empty = $this->composer('   ');
        $long = $this->composer(str_repeat('a', 20001));
        Http::fake();
        $this->assertSame(1, $this->runCommand());
        Http::assertNothingSent();
        $this->assertSame('   ', $empty->fresh()->biography);
        $this->assertSame($long->biography, $long->fresh()->biography);
        $this->assertStringContainsString('0 saved, 1 empty bios skipped, 1 failed', $this->output);
        $this->assertStringContainsString('--composer='.$long->id, $this->output);
        $this->assertSame([], $this->backup());
    }

    public function test_a_failed_response_keeps_its_source_and_does_not_stop_other_composers()
    {
        $first = $this->composer();
        $second = $this->composer();
        Http::fake(['*' => Http::sequence()->push(['error' => 'secret-upstream-text'], 429)->push($this->response())]);
        $this->assertSame(1, $this->runCommand());
        Http::assertSentCount(2);
        $this->assertSame('Original source', $first->fresh()->biography);
        $this->assertSame("Early life.\n\nMusic.\n\nLater life.", $second->fresh()->biography);
        $this->assertSame([$second->id], array_column($this->backup(), 'id'));
        $this->assertStringContainsString('--composer='.$first->id, $this->output);
        $this->assertStringNotContainsString('secret-upstream-text', $this->output);
        $this->assertStringNotContainsString('fake-review-key', $this->output);
    }

    public function test_selected_ids_allow_retrying_without_rewriting_successful_composers()
    {
        $first = $this->composer();
        $second = $this->composer();
        $third = $this->composer();
        Http::fake(['*' => Http::response($this->response())]);
        $this->assertSame(0, $this->runCommand(['--composer' => [(string) $first->id, (string) $third->id, (string) $first->id]]));
        Http::assertSentCount(2);
        $this->assertSame('Original source', $second->fresh()->biography);
        $this->assertSame([$first->id, $third->id], array_column($this->backup(), 'id'));
    }

    public function test_every_composer_is_processed_once_across_database_chunks()
    {
        for ($i = 0; $i < 51; $i++) $this->composer('Source '.$i);
        Http::fake(['*' => Http::response($this->response())]);
        $this->assertSame(0, $this->runCommand());
        Http::assertSentCount(51);
        $this->assertSame(51, Composer::where('biography', "Early life.\n\nMusic.\n\nLater life.")->count());
        $backup = $this->backup();
        $this->assertCount(51, $backup);
        $this->assertCount(51, array_unique(array_column($backup, 'id')));
    }

    /** @dataProvider invalidIds */
    public function test_invalid_or_missing_ids_fail_before_any_api_calls_or_backup($id)
    {
        $this->composer();
        Http::fake();
        $this->assertSame(1, $this->runCommand(['--composer' => [$id]]));
        Http::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public static function invalidIds(): array
    {
        return [['0'], ['-1'], ['1.5'], ['1 or 1=1'], ['999999'], ['9999999999999999999999999']];
    }

    public function test_a_case_only_edit_during_generation_is_preserved()
    {
        $composer = $this->composer('Original source');
        Http::fake(function () use ($composer) {
            DB::table('composers')->where('id', $composer->id)->update(['biography' => 'ORIGINAL source']);
            return Http::response($this->response());
        });
        $this->assertSame(1, $this->runCommand());
        $this->assertSame('ORIGINAL source', $composer->fresh()->biography);
        $this->assertStringContainsString('pending draft was not saved', $this->output);
        $this->assertSame('Original source', $this->backup()[0]['original_biography']);
    }

    public function test_a_save_failure_rolls_back_and_processing_continues()
    {
        $first = $this->composer();
        $second = $this->composer();
        Composer::updated(function ($composer) use ($first) {
            if ($composer->id === $first->id) throw new \RuntimeException('secret-database-error');
        });
        Http::fake(['*' => Http::response($this->response())]);
        $this->assertSame(1, $this->runCommand());
        $this->assertSame('Original source', $first->fresh()->biography);
        $this->assertSame("Early life.\n\nMusic.\n\nLater life.", $second->fresh()->biography);
        $this->assertCount(2, $this->backup());
        $this->assertStringNotContainsString('secret-database-error', $this->output);
    }

    public function test_backup_write_failure_stops_before_saving_or_generating_more_bios()
    {
        $first = $this->composer();
        $second = $this->composer();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('path')->once()->andReturn('/private/backup.jsonl');
        $disk->shouldReceive('append')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        Http::fake(['*' => Http::response($this->response())]);
        $this->assertSame(1, $this->runCommand());
        Http::assertSentCount(1);
        $this->assertSame('Original source', $first->fresh()->biography);
        $this->assertSame('Original source', $second->fresh()->biography);
        $this->assertStringContainsString('1 composers were not processed', $this->output);
        $this->assertStringContainsString('--composer='.$first->id.' --composer='.$second->id, $this->output);
    }

    public function test_backup_creation_failure_prevents_all_api_calls()
    {
        $this->composer();
        $disk = \Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($disk);
        Http::fake();
        $this->assertSame(1, $this->runCommand());
        Http::assertNothingSent();
    }

    public function test_missing_key_and_no_composers_do_not_call_the_api()
    {
        Http::fake();
        config(['services.openai.key' => null]);
        $this->assertSame(1, $this->runCommand());
        config(['services.openai.key' => 'fake-review-key']);
        $this->assertSame(0, $this->runCommand());
        Http::assertNothingSent();
        $this->assertSame([], Storage::disk('local')->allFiles());
    }
}
