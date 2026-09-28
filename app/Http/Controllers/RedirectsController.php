<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectsController extends Controller
{
    public function admin(Request $request)
    {
        $suffix = substr($request->getRequestUri(), strlen('/admin'));

        return redirect()->away(rtrim(route('admin.home'), '/').$suffix, 301);
    }

    public function youtube()
    {
    	return redirect(config('services.channels.youtube'));
    }

    public function ios()
    {
    	return redirect(config('app.stores.ios'));
    }
}
