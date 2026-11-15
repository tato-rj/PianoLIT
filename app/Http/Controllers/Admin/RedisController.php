<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Collections\Catalog;
use App\Services\DiscoverRefresh;

class RedisController extends Controller
{
    public function __construct(private Catalog $catalog)
    {
    }

    public function update(DiscoverRefresh $refresh)
    {
        $refresh->refresh();
        $this->catalog->rotateWeeklySelection();

        return back()->with('status', 'Discover and Collections have been refreshed.');
    }
}
