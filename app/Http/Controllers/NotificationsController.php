<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function read(Request $request)
    {
    	return $request->all();
    	if ($request->has('ids')) {
    		foreach (json_decode($request->ids) as $id) {
		    	auth()->user()->notifications()->find($id)->markAsRead();
    		}
    	}

    	auth()->user()->notifications->markAsRead();

    	return redirect()->to($request->url);
    }
}
