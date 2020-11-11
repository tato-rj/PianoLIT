<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RedirectsController extends Controller
{
    public function youtube()
    {
    	return redirect(config('services.channels.youtube'));
    }

    public function ios()
    {
    	return redirect(config('app.stores.ios'));
    }
}
