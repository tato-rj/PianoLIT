<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        return Composer::inRandomOrder()->take(2)->get();
    }
}
