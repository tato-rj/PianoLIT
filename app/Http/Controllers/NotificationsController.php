<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function read(Request $request)
    {
    	if ($request->has('id'))
	    	return auth()->user()->notifications()->find($request->id)->markAsRead();

    	auth()->user()->notifications->markAsRead();

    	return redirect()->back();
    }
}
