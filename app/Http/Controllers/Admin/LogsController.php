<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class LogsController extends Controller
{
    public function data(Request $request)
    {
    	return view('admin.pages.users.show.log-data', ['data' => json_decode($request->data), 'url' => json_decode($request->url)])->render();
    }
}
