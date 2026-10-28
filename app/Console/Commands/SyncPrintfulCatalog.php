<?php

namespace App\Console\Commands;

use App\Services\Admin\PrintfulCatalogSync;
use App\Services\Shop\PrintfulException;
use Illuminate\Console\Command;

class SyncPrintfulCatalog extends Command
{
    protected $signature = 'printful:sync-catalog';
    protected $description = 'Automatically retire merchandise products deleted from Printful';

    public function handle(PrintfulCatalogSync $sync): int
    {
        try {
            $removed = $sync->reconcile();
        } catch (PrintfulException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }
        $this->info('Printful catalog checked; '.$removed.' deleted product(s) removed from the local catalog.');
        return 0;
    }
}
