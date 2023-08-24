<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Performance;

class PerformancesController extends Controller
{
    public function clap(Performance $performance)
    {
        $this->authorize('clap', $performance);

        $performance->clap();

        return response(200);
    }
}
