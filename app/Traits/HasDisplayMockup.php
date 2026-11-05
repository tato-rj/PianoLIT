<?php

namespace App\Traits;

use App\Services\Shop\DisplayMockups;
use Illuminate\Support\Facades\DB;

trait HasDisplayMockup
{
    public function initializeHasDisplayMockup()
    {
        // This web-only artwork must never add fields to existing mobile JSON.
        $this->makeHidden('display_mockup_path');
        $this->mergeGuarded(['display_mockup_path']);
    }

    public static function bootHasDisplayMockup()
    {
        static::deleted(function ($product) {
            // Retired Printful products remain recoverable with their curated artwork.
            if (method_exists($product, 'isForceDeleting') && !$product->isForceDeleting()) return;
            $path = $product->display_mockup_path;
            DB::afterCommit(fn () => app(DisplayMockups::class)->discard($path));
        });
    }

    public function displayMockupUrl(): ?string
    {
        return DisplayMockups::url($this->display_mockup_path);
    }
}
