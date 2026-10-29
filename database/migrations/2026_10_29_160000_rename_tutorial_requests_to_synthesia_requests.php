<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\{DB, Schema};

class RenameTutorialRequestsToSynthesiaRequests extends Migration
{
    public function up()
    {
        $this->assertNoEmailKeyConflicts('tutorial', 'synthesia');
        // Rename in place: IDs, original type selections and dates remain intact.
        $this->renameTable('tutorial_requests', 'synthesia_requests');
        $this->renameEmailKeys('tutorial', 'synthesia');
    }

    public function down()
    {
        $this->assertNoEmailKeyConflicts('synthesia', 'tutorial');
        $this->renameTable('synthesia_requests', 'tutorial_requests');
        $this->renameEmailKeys('synthesia', 'tutorial');
    }

    private function renameTable($from, $to)
    {
        $sourceExists = Schema::hasTable($from);
        $targetExists = Schema::hasTable($to);
        if ($sourceExists === $targetExists) {
            throw new \RuntimeException('Expected exactly one request table before the Synthesia rename.');
        }
        // MySQL DDL commits separately. Resume safely if a prior process stopped
        // after the table rename but before saving settings/migration history.
        if ($sourceExists) Schema::rename($from, $to);
    }

    private function assertNoEmailKeyConflicts($from, $to)
    {
        foreach (['request', 'published'] as $suffix) {
            if (DB::table('automatic_email_settings')->where('email_key', $from.'-'.$suffix)->exists()
                && DB::table('automatic_email_settings')->where('email_key', $to.'-'.$suffix)->exists()) {
                throw new \RuntimeException('Conflicting request email switches; resolve before renaming the request table.');
            }
        }
    }

    private function renameEmailKeys($from, $to)
    {
        DB::transaction(function () use ($from, $to) {
            foreach (['request', 'published'] as $suffix) {
                $old = $from.'-'.$suffix;
                $new = $to.'-'.$suffix;
                // A collision must fail rather than silently resetting a saved switch.
                DB::table('automatic_email_settings')->where('email_key', $old)->update(['email_key' => $new]);
            }
        });
    }
}
