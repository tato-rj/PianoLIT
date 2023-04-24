<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{EmailList, Subscription, EmailLog};
use App\Events\Emails\{EmailListSent, Unsubscribed};
use App\Jobs\{SendMassEmails, SendEmail};

class EmailListsController extends Controller
{
    public function index()
    {
        $lists = EmailList::all();

        return view('admin.pages.subscriptions.lists.index', compact('lists'));
    }

    public function reports()
    {
        $reports = EmailLog::generate()->get();

        return view('admin.pages.reports.index', compact('reports'));
    }

    public function report($list)
    {
        if (request()->ajax())
            return EmailLog::datatable($list);

        $report = EmailLog::byList($list)->get();
        $event = EmailLog::generate($list)->first();

        return view('admin.pages.reports.show.index', compact(['report', 'event']));
    }

    public function send(EmailList $list)
    {
        $list->send();

        event(new EmailListSent($list));
        // $this->dispatch(new SendMassEmails($list));

        return back()->with('status', 'The list email is being sent to all susbcribers, please allow a few seconds to complete.');
    }
    
    public function sendTo(Request $request, EmailList $list)
    {
        \Mail::to($request->email)->send($list->mailable($list->listId(), Subscription::byEmail($request->email)->first()));

    	return back()->with('status', 'A preview was sent to ' . $request->email);
    }

    public function preview(EmailList $list)
    {
    	return $list->mailable('preview');
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

    public function destroyReport($list_id)
    {
        EmailLog::byList($list_id)->delete();

        return redirect(route('admin.subscriptions.reports.index'))->with('status', 'The list has been deleted');
    }
}
