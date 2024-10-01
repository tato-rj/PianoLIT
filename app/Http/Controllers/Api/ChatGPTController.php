<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Composer;

class ChatGPTController extends Controller
{
    public function composer(Request $request)
    {
        $results = Composer::where('name', 'LIKE', '%'.$request->name.'%')->take(5)->get();

        return response()->json(compact('results'));
    }

}
