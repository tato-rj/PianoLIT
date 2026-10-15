<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class DesignSystemController extends Controller
{
    public function index()
    {
        return view('admin.pages.settings.index');
    }
}
