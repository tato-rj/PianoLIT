<?php

namespace App\Http\Controllers\WebApp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Blog\Post;

class BlogController extends Controller
{
    public function show(Post $post)
    {
    	return view('webapp.blog.show', compact('post'));
    }
}
