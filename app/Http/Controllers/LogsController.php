<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogsController extends Controller
{
    public function data(Request $request)
    {
    	return view('admin.pages.users.show.log-data', ['data' => json_decode($request->data)])->render();
    }
}
