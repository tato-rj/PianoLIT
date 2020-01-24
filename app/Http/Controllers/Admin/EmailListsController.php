<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{EmailList, Subscription};
use App\Events\Emails\{EmailListSent, Unsubscribed};

class EmailListsController extends Controller
{
    public function index()
    {
        $lists = EmailList::all();

        return view('admin.pages.subscriptions.lists.index', compact('lists'));
    }

    public function send(EmailList $list)
    {
    	$list->send();

        event(new EmailListSent($list));

        return back()->with('status', 'The list email was sent to all susbcribers.');
    }
    
    public function sendTo(Request $request, EmailList $list)
    {
    	\Mail::to($request->email)->send($list->mailable(Subscription::byEmail($request->email)->first()));

    	return back()->with('status', 'A preview was sent to ' . $request->email);
    }

    public function preview(EmailList $list)
    {
    	return $list->mailable();
    }

    public function store(Request $request)
    {
        EmailList::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return back()->with('status', 'The list has been created.');
    }

    public function edit(EmailList $list)
    {
        if (request()->ajax())
            return EmailList::datatable($list);

        return view('admin.pages.subscriptions.lists.edit', compact('list'));
    }

    public function update(Request $request, EmailList $list)
    {
        $list->update([
            'name' => $request->name,
            'description' => $request->description
        ]);
        
        return back()->with('status', 'This list has been updated.');
    }

    public function status(Request $request, EmailList $list)
    {
        if ($list->subscribers->contains($request->subscriberId)) {
            $list->remove(Subscription::find($request->subscriberId));
        } else {
            $list->add(Subscription::find($request->subscriberId));     
        }
        
        return response()->json(['status' => 'This subscription has been updated.']);
    }

    public function destroy(EmailList $list)
    {
        $list->delete();

        return back()->with('status', 'The list has been deleted');
    }
}
