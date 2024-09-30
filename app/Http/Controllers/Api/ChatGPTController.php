<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        $collection = \Cache::remember('api.chatgpt.composers', days(1), function() {
            return Composer::all();
        });

        return $collection;
    }
}
