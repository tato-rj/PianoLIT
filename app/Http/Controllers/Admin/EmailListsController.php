<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{EmailList, Subscription, EmailLog};
use App\Events\Emails\Unsubscribed;

class EmailListsController extends Controller
{
    public function index()
    {
        $lists = EmailList::all();

        return view('admin.pages.subscriptions.lists.index', compact('lists'));
    }

    public function reports()
    {
        if (request()->ajax())
            return $this->reportsDatatable(request());

        return view('admin.pages.reports.index');
    }

    protected function reportsDatatable(Request $request)
    {
        $start = max((int) $request->input('start', 0), 0);
        $length = min(max((int) $request->input('length', 10), 1), 100);
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $orderColumn = (int) $request->input('order.0.column', 1);
        $sortField = $request->input("columns.{$orderColumn}.data", 'sent_at');
        $search = trim($request->input('search.value', ''));
        $baseQuery = EmailLog::query()->whereNotNull('list_id');
        $recordsTotal = (clone $baseQuery)->distinct()->count('list_id');

        if ($search !== '') {
            $baseQuery->where('list_id', 'like', '%'.str_replace(' ', '-', strtolower($search)).'%');
        }

        $recordsFiltered = (clone $baseQuery)->distinct()->count('list_id');
        $columns = [
            'sent_at' => 'sent_at_sort',
            'name' => 'list_id',
            'emails_count' => 'emails_count',
            'delivered' => 'delivered_count',
            'failed' => 'failed_count',
            'opened' => 'opens_count',
            'clicked' => 'clicks_count',
        ];
        $orderBy = $columns[$sortField] ?? 'sent_at_sort';
        $reports = $baseQuery
            ->selectRaw('list_id,
                MAX(created_at) as sent_at_sort,
                COUNT(*) as emails_count,
                SUM(unique_delivered) as delivered_count,
                SUM(unique_failed) as failed_count,
                SUM(unique_opened) as opens_count,
                SUM(unique_clicked) as clicks_count')
            ->groupBy('list_id')
            ->orderBy($orderBy, $direction)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $reports->map(function ($report) {
            return [
                'checkbox' => view('admin.pages.reports.table.checkbox', compact('report'))->render(),
                'sent_at' => $report->sent_at->toFormattedDateString(),
                'name' => $report->name,
                'emails_count' => $report->emails_count,
                'delivered' => $this->reportPercentage($report->delivered_count, $report->emails_count, 'delivered'),
                'failed' => $this->reportPercentage($report->failed_count, $report->emails_count, 'failed'),
                'opened' => $this->reportPercentage($report->opens_count, $report->emails_count, 'opened'),
                'clicked' => $this->reportPercentage($report->clicks_count, $report->emails_count, 'clicked'),
                'actions' => view('admin.pages.reports.table.actions', compact('report'))->render(),
            ];
        });

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    protected function reportPercentage($count, $total, $label)
    {
        return '<span title="'.$count.' '.$label.'">'.percentage($count, $total).'%</span>';
    }

    public function report($list)
    {
        if (request()->ajax())
            return EmailLog::datatable($list);

        $report = EmailLog::byList($list)->get();
        $event = EmailLog::generate($list)->first();

        return view('admin.pages.reports.show.index', compact(['report', 'event']));
    }

    public function send(Request $request, EmailList $list)
    {
        if ($list->last_sent_at && $list->last_sent_at->gt(now()->subDay()))
            return back()->with('status', 'This list has recently been sent');

        $list->send($request->subject);

        return back()->with('status', 'The list email has been queued and will be sent to all subscribers in the background.');
    }
    
    public function sendTo(Request $request, EmailList $list)
    {
        \Mail::to($request->email)->queue($list->mailable($list->listId(), Subscription::byEmail($request->email)->first()));

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

    public function destroyManyReports(Request $request)
    {
        EmailLog::byLists($request->ids)->delete();

        return redirect(route('admin.subscriptions.reports.index'))->with('status', 'The lists have been deleted');
    }
}
