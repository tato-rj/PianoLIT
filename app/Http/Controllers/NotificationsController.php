<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function read(Request $request)
    {

    	if ($request->has('ids')) {
    		return $request->all();
    		foreach (json_decode($request->ids) as $id) {
		    	auth()->user()->notifications()->find($id)->markAsRead();
    		}
    	}

    	// auth()->user()->notifications->markAsRead();

    	return redirect()->to($request->url);
    }
}
