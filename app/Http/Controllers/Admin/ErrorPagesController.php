<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ErrorPagesController extends Controller
{
    public function index()
    {
        return view('admin.pages.settings.error-pages');
    }
}
