<?php

namespace App\Console\Commands;

use App\Services\Shop\{Printful, PrintfulException};
use Illuminate\Console\Command;

class CheckPrintfulConnection extends Command
{
    protected $signature = 'printful:check';
    protected $description = 'Check the configured Printful store and read its product count';

    public function handle(Printful $printful): int
    {
        try {
            $storeId = $printful->storeId();
            $store = collect($printful->stores())->first(function ($store) use ($storeId) {
                return is_array($store) && (string) ($store['id'] ?? '') === $storeId;
            });
            if (!$store) {
                throw new PrintfulException('The token does not include the configured Printful store.');
            }
            $products = $printful->products();
        } catch (PrintfulException $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        $this->info('Connected to Printful store '.$storeId);
        $count = $products['paging']['total'] ?? count($products['result']);
        $this->line('Store products: '.(int) $count);
        $this->line('Read-only check complete');

        return 0;
    }
}
