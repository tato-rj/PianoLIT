<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composers(Request $request)
    {
        // if ($request->header('auth_token') != env('CHATGPT_TOKEN'))
        //     abort(404);

        return response(200);
    }
}
