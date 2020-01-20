<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\EmailList;

class EmailsController extends Controller
{
    public function lists()
    {
        return view('admin.pages.subscriptions.lists.index');    
    }

    public function send(EmailList $list)
    {
    	$list->send();
    }
    
    public function sendTo(Request $request, EmailList $list)
    {
    	\Mail::to($request->email)->send($list->mailable());

    	return back()->with('status', 'A preview was sent to ' . $request->email);
    }

    public function preview(EmailList $list)
    {
    	return $list->mailable();
    }
}
