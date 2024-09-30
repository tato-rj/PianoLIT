<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        if ($request->bearerToken() != env('CHATGPT_TOKEN'))
            abort(404);
// response()->json(['message' => 'Unauthorized'], 401);
        
        return Composer::inRandomOrder()->take(2)->get();
    }
}
