<?php

namespace App\Console\Commands;

use App\Services\Pieces\ScoreSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, File, Schema};

class BackfillPieceScoreSources extends Command
{
    protected $signature = 'pieces:backfill-score-sources
        {--apply : Save the detected PianoLIT eScores}
        {--reference-dir= : Directory of verified PianoLIT eScore PDFs to match by checksum}';

    protected $description = 'Find PianoLIT eScores among the PDFs saved on pieces';

    public function handle(ScoreSource $source): int
    {
        if (!Schema::hasColumn('pieces', 'score_source')) {
            $this->error('Run the piece score source migration first.');
            return 1;
        }
        if (!$source->executable()) {
            $this->error('Install Poppler (pdftotext) before scanning scores.');
            return 1;
        }

        $references = [];
        if ($directory = $this->option('reference-dir')) {
            if (!is_dir($directory) || !is_readable($directory)) {
                $this->error('The reference directory is missing or unreadable.');
                return 1;
            }
            foreach (File::allFiles($directory) as $file) {
                if (strtolower($file->getExtension()) !== 'pdf') {
                    continue;
                }
                $hash = @hash_file('sha256', $file->getPathname());
                if ($hash === false) {
                    $this->error('A reference PDF could not be read.');
                    return 1;
                }
                $references[$hash] = true;
            }
            if (!$references) {
                $this->error('The reference directory does not contain any PDFs.');
                return 1;
            }
        }

        $apply = $this->option('apply');
        $checked = $missing = $labeled = $matched = $saved = $unknown = 0;
        foreach (DB::table('pieces')->select('id', 'score_path', 'score_source')->lazyById(100) as $piece) {
            $checked++;
            if (!$piece->score_path) {
                $missing++;
                continue;
            }
            if ($piece->score_source !== null) {
                $labeled++;
                continue;
            }
            [$detected, $reason] = $source->inspectSaved($piece->score_path, $references);
            $message = "Piece {$piece->id} ($reason)";
            if ($detected !== ScoreSource::PIANOLIT) {
                $unknown++;
                $this->line("Unknown: $message", null, 'v');
                continue;
            }

            $matched++;
            if (!$apply) {
                $this->line("PianoLIT eScore: $message");
                continue;
            }

            // Only label the PDF we checked and respect a concurrent editor choice.
            $updated = DB::table('pieces')->where('id', $piece->id)->where('score_path', $piece->score_path)
                ->whereNull('score_source')->update(['score_source' => $detected]);
            $saved += $updated;
            if ($updated) {
                $this->line("Marked as PianoLIT eScore: $message");
            } else {
                $this->warn("Skipped $message because the PDF or source changed.");
            }
        }

        $this->info("Checked $checked pieces. No saved PDF: $missing. Already labeled: $labeled. Matched: $matched. Unknown: $unknown. Saved: $saved.");
        if (!$apply) {
            $this->line('Preview only. Run again with --apply to save the matches.');
        }

        return 0;
    }
}
