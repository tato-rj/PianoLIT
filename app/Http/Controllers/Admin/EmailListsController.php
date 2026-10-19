<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{EmailCampaignReport, EmailList, Subscription, EmailLog, Piece};
use App\Services\Admin\EmailListSubscribersTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmailListsController extends Controller
{
    public function index()
    {
        $lists = EmailList::where('name', EmailList::FREE_PICK)->orderBy('id')->get();
        $hasFreePick = Piece::free()->exists();
        $recipient = auth('admin')->user()->email;

        return view('admin.pages.subscriptions.lists.index', compact('lists', 'hasFreePick', 'recipient'));
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
        $baseQuery = EmailCampaignReport::query();
        $recordsTotal = (clone $baseQuery)->count();

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('list_id', 'like', '%'.str_replace(' ', '-', strtolower($search)).'%');
            });
        }

        $recordsFiltered = (clone $baseQuery)->count();
        $columns = [
            'sent_at' => 'sent_at',
            'name' => 'name',
            'emails_count' => 'emails_count',
            'delivered' => 'delivered_count',
            'failed' => 'failed_count',
            'opened' => 'opens_count',
            'clicked' => 'clicks_count',
        ];
        $orderBy = $columns[$sortField] ?? 'sent_at';
        $reports = $baseQuery
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
        $this->assertFreePick($list);
        if (! Piece::free()->exists())
            return back()->with('error', 'Choose a current free pick before sending this list.');

        if ($list->last_sent_at && $list->last_sent_at->gt(now()->subDay()))
            return back()->with('status', 'This list has recently been sent');

        $list->send();

        return back()->with('status', 'The list email has been queued and will be sent to all subscribers in the background.');
    }

    public function sendTo(Request $request, EmailList $list)
    {
        $this->assertFreePick($list);
        $data = $request->validate(['email' => 'required|string|email|max:254']);
        if (! Piece::free()->exists())
            return back()->withInput()->with('error', 'Choose a current free pick before sending a preview.');

        try {
            \Mail::to($data['email'])->queue($list->mailable('preview'));
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'The preview could not be queued. Please try again.');
        }

        return back()->with('status', 'A preview was queued for ' . $data['email']);
    }

    public function preview(EmailList $list)
    {
        $this->assertFreePick($list);
        abort_unless(Piece::free()->exists(), 422, 'Choose a current free pick before previewing this email.');

        return $list->mailable('preview');
    }

    public function store(Request $request)
    {
        $data = $this->listData($request);
        $request->validate(['name' => Rule::unique('email_lists', 'name')]);
        EmailList::create($data);

        return back()->with('status', 'The list has been created.');
    }

    public function edit(Request $request, EmailList $list, EmailListSubscribersTable $table)
    {
        $this->assertFreePick($list);

        return view('admin.pages.subscriptions.lists.edit', ['list' => $list] + $table->get($request, $list));
    }

    public function update(Request $request, EmailList $list)
    {
        $this->assertFreePick($list);
        $list->update($this->listData($request));

        return back()->with('status', 'This list has been updated.');
    }

    public function status(Request $request, EmailList $list)
    {
        $this->assertFreePick($list);
        $data = $request->validate(['subscriberId' => 'required|integer|min:1|exists:subscriptions,id', 'enabled' => 'sometimes|required|boolean']);
        $subscriber = Subscription::findOrFail($data['subscriberId']);
        $enabled = array_key_exists('enabled', $data) ? (bool) $data['enabled'] : ! $list->has($subscriber->email);
        if ($enabled) {
            $list->add($subscriber);
        } else {
            $list->remove($subscriber);
        }

        return $request->expectsJson()
            ? response()->json(['status' => 'This subscription has been updated.'])
            : back()->with('status', 'This subscription has been updated.');
    }

    public function destroy(EmailList $list)
    {
        DB::transaction(function () use ($list) { $list->delete(); });

        return back()->with('status', 'The list has been deleted');
    }

    public function destroyReport($list_id)
    {
        EmailLog::byList($list_id)->delete();
        EmailCampaignReport::where('list_id', $list_id)->delete();

        return redirect(route('admin.subscriptions.reports.index'))->with('status', 'The list has been deleted');
    }

    public function destroyManyReports(Request $request)
    {
        EmailLog::byLists($request->ids)->delete();
        EmailCampaignReport::whereIn('list_id', $request->ids)->delete();

        return redirect(route('admin.subscriptions.reports.index'))->with('status', 'The lists have been deleted');
    }

    private function assertFreePick(EmailList $list)
    {
        abort_unless($list->name === EmailList::FREE_PICK, 404);
    }

    private function listData(Request $request)
    {
        return $request->validate([
            'name' => ['required', 'string', Rule::in([EmailList::FREE_PICK])],
            'description' => 'required|string|max:2000',
        ]);
    }
}
