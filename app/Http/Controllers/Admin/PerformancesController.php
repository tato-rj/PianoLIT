<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Performance;
use App\Events\Performances\PerformanceApproved;

class PerformancesController extends Controller
{
    public function approve(Performance $performance)
    {
        $performance->approve();

        event(new PerformanceApproved($performance));

        return back()->with('status', 'The performance is live.');
    }

    public function destroy(Performance $performance)
    {
        $performance->delete();

        return back()->with('status', 'The performance has been deleted.');
    }
}
