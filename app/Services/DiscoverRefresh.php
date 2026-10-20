<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class DiscoverRefresh
{
    public function refresh()
    {
        // Keep the same daily cache prefixes used by the admin refresh button.
        foreach (['app.discover', 'app.explore'] as $key) {
            if (!Redis::set($key, $key.'-'.now()->timestamp)) {
                throw new \RuntimeException('The Discover cache could not be refreshed.');
            }
        }
    }
}
