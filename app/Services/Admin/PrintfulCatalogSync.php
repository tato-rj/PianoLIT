<?php

namespace App\Services\Admin;

use App\Merchandise\MerchandiseProduct;
use App\Services\Shop\{Printful, PrintfulException, PrintfulProductNotFound};
use Illuminate\Support\Facades\DB;

class PrintfulCatalogSync
{
    /** Retire only products absent from a complete catalog AND confirmed missing. */
    public function reconcile(): int
    {
        $api = app(Printful::class);
        if (!$api->enabled()) return 0;
        $storeId = $api->storeId();
        $local = MerchandiseProduct::where('printful_store_id', $storeId)->get(['id', 'printful_product_id']);
        if ($local->isEmpty()) return 0;

        $stores = $api->stores();
        if (!collect($stores)->contains(fn ($store) => is_array($store) && (string) ($store['id'] ?? '') === $storeId)) {
            throw new PrintfulException('The token does not include the configured Printful store.');
        }
        $deadline = microtime(true) + 10;
        $remoteIds = $this->completeCatalog($api, $deadline);
        $removed = 0;
        foreach ($local as $candidate) {
            if (isset($remoteIds[(string) $candidate->printful_product_id])) continue;
            if (microtime(true) >= $deadline) break;
            try {
                // A list can shift while paging. An existing product must survive.
                $api->product((int) $candidate->printful_product_id);
                continue;
            } catch (PrintfulProductNotFound $exception) {
                $removed += DB::transaction(function () use ($candidate, $storeId) {
                    $product = MerchandiseProduct::whereKey($candidate->id)->where('printful_store_id', $storeId)
                        ->where('printful_product_id', $candidate->printful_product_id)->lockForUpdate()->first();
                    if (!$product) return 0;
                    $product->revision++;
                    $product->save();
                    $product->delete();
                    return 1;
                });
            }
        }
        return $removed;
    }

    private function completeCatalog(Printful $api, float $deadline): array
    {
        $ids = [];
        $offset = 0;
        $total = null;
        do {
            if (microtime(true) >= $deadline || $offset >= 10000) {
                throw new PrintfulException('Printful catalog verification did not finish. Existing products have been kept.');
            }
            $page = $api->products($offset, 100);
            $paging = $page['paging'] ?? [];
            if (!isset($paging['offset'], $paging['total']) || !is_int($paging['offset']) || !is_int($paging['total'])
                || $paging['offset'] !== $offset || $paging['total'] < 0 || ($total !== null && $total !== $paging['total'])
                || ($paging['total'] > $offset && count($page['result']) === 0)) {
                throw new PrintfulException('Printful returned an incomplete catalog. Existing products have been kept.');
            }
            $total = $paging['total'];
            foreach ($page['result'] as $row) {
                $id = is_array($row) ? ($row['id'] ?? null) : null;
                if ((!is_int($id) && !is_string($id)) || !preg_match('/^[1-9][0-9]*$/D', (string) $id) || isset($ids[(string) $id])) {
                    throw new PrintfulException('Printful returned an incomplete catalog. Existing products have been kept.');
                }
                $ids[(string) $id] = true;
            }
            $offset += count($page['result']);
        } while ($offset < $total);
        if (count($ids) !== $total) throw new PrintfulException('Printful returned an incomplete catalog. Existing products have been kept.');
        return $ids;
    }
}
