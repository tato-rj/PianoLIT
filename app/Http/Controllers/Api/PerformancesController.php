<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\{Performance, User};

class PerformancesController extends Controller
{
    public function clap(Request $request, Performance $performance)
    {
        if ($performance->user_id == $request->user_id)
            throw new \Illuminate\Auth\Access\AuthorizationException('You cannot clap to your own performance');

        $performance->clap(User::find($request->user_id));

        return ['claps_sum' => $performance->claps_sum];
    }
}
