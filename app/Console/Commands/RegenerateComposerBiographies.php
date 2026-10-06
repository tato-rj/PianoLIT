<?php

namespace App\Console\Commands;

use App\Composer;
use App\Services\OpenAI\{BiographyGenerationException, ComposerBiography};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;

class RegenerateComposerBiographies extends Command
{
    protected $signature = 'composers:regenerate-bios
        {--composer=* : Only process these composer IDs; repeat the option to retry several composers}';

    protected $description = 'Rewrite and save composer bios using the same OpenAI service as the admin editor.';

    public function handle(ComposerBiography $generator)
    {
        if (! config('services.openai.key')) {
            $this->error('Bio regeneration is not configured. Set the server OpenAI API key.');
            return 1;
        }

        $ids = $this->option('composer');
        foreach ($ids as $id) {
            if (! ctype_digit((string) $id) || (int) $id < 1 || (string) (int) $id !== (string) $id) {
                $this->error('Each --composer option must be a positive integer ID.');
                return 1;
            }
        }
        $ids = array_unique(array_map('intval', $ids));
        $query = Composer::query()->setEagerLoads([])->select(['id', 'name', 'biography']);
        if ($ids) {
            $query->whereIn('id', $ids);
            if ((clone $query)->count() !== count($ids)) {
                $this->error('One or more selected composers do not exist.');
                return 1;
            }
        }

        $total = (clone $query)->count();
        if (! $total) {
            $this->info('No composers to process.');
            return 0;
        }

        $disk = Storage::disk('local');
        $backup = 'composer-bio-backups/'.now()->format('Ymd-His').'-'.Str::uuid().'.jsonl';
        try {
            if (! $disk->put($backup, '', ['visibility' => 'private'])) {
                throw new \RuntimeException('Backup unavailable.');
            }
        } catch (\Throwable $exception) {
            $this->error('Could not create the original-bio backup. No API requests or updates were made.');
            return 1;
        }

        $this->info("Rewriting {$total} composers using ".config('services.openai.model').'. Successful drafts will be saved immediately.');
        $this->line('Original text and generated drafts: '.$disk->path($backup));
        $processed = $updated = $skipped = $lastId = 0;
        $failed = [];
        $halted = false;

        $query->chunkById(50, function ($composers) use ($generator, $disk, $backup, $total, &$processed, &$updated, &$skipped, &$lastId, &$failed, &$halted) {
            foreach ($composers as $composer) {
                $lastId = $composer->id;
                $prefix = '['.(++$processed)."/{$total}] Composer #{$composer->id}: ";
                $source = (string) $composer->biography;
                if (trim($source) === '') {
                    $skipped++;
                    $this->line($prefix.'skipped (empty bio).');
                    continue;
                }
                if (mb_strlen($source) > 20000) {
                    $failed[] = $composer->id;
                    $this->warn($prefix.'source exceeds the editor limit of 20000 characters.');
                    continue;
                }

                try {
                    $draft = $generator->generate($composer->name, $source);
                } catch (BiographyGenerationException $exception) {
                    $failed[] = $composer->id;
                    $this->warn($prefix.$exception->getMessage());
                    continue;
                } catch (\Throwable $exception) {
                    // Never print raw upstream exceptions, request bodies, or credentials.
                    $failed[] = $composer->id;
                    $this->warn($prefix.'generation failed unexpectedly; the bio was not changed.');
                    continue;
                }

                // Persist recovery data before any database update. Entries describe
                // attempted saves, including a save that later fails or conflicts.
                try {
                    $entry = json_encode(['id' => $composer->id, 'name' => $composer->name,
                        'original_biography' => $source, 'generated_biography' => $draft],
                        JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    if (! $disk->append($backup, $entry."\n", '')) {
                        throw new \RuntimeException('Backup unavailable.');
                    }
                } catch (\Throwable $exception) {
                    $failed[] = $composer->id;
                    $halted = true;
                    $this->error($prefix.'could not back up the original text; stopped without saving this draft.');
                    return false;
                }

                try {
                    // Do not hold a database lock during the network request. Compare
                    // the actual strings under a short lock, avoiding collation-based
                    // comparisons that can miss case-only edits on MySQL.
                    $saved = DB::transaction(function () use ($composer, $source, $draft) {
                        $current = Composer::query()->setEagerLoads([])->select(['id', 'name', 'biography'])
                            ->whereKey($composer->id)->lockForUpdate()->first();
                        if (! $current || $current->name !== $composer->name || $current->biography !== $source) {
                            return false;
                        }
                        $current->biography = $draft;
                        if (! $current->save()) {
                            throw new \RuntimeException('Save was cancelled.');
                        }
                        return true;
                    });
                } catch (\Throwable $exception) {
                    $failed[] = $composer->id;
                    $this->warn($prefix.'could not complete the save; original text is in the backup.');
                    continue;
                }

                if (! $saved) {
                    $failed[] = $composer->id;
                    $this->warn($prefix.'name or bio changed, or composer was removed; pending draft was not saved.');
                    continue;
                }
                $updated++;
                $this->info($prefix.'saved.');
            }
        });

        $this->info("Finished: {$updated} saved, {$skipped} empty bios skipped, ".count($failed).' failed.');
        if ($halted) {
            $this->warn('Stopped early because backup storage failed. '.($total - $processed).' composers were not processed.');
        }
        $retry = $failed;
        if ($halted) {
            $retry = array_merge($retry, (clone $query)->where('id', '>', $lastId)->pluck('id')->all());
        }
        if ($retry) {
            $this->line('Retry unfinished composers: php artisan composers:regenerate-bios '.implode(' ', array_map(function ($id) {
                return '--composer='.$id;
            }, $retry)));
        }

        return $failed ? 1 : 0;
    }
}
