<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    public function read(Request $request)
    {
    	if ($request->has('ids')) {
    		foreach ($request->ids as $id) {
		    	auth()->user()->notifications()->find($id)->markAsRead();
    		}
    	} else {
	    	auth()->user()->notifications()->update(['read_at' => now()]);
    	}

        if ($request->wantsJson())
            return response(200);

    	return redirect()->to($request->url);
    }

    public function unread(Request $request)
    {
        if ($request->has('ids')) {
            foreach ($request->ids as $id) {
                auth()->user()->notifications()->find($id)->update(['read_at' => null]);
            }
        }

        if ($request->wantsJson())
            return response(200);

        return redirect()->to($request->url); 
    }
}
