<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\{EmailCampaignReport, EmailList, Subscription, EmailLog, Piece};
use App\Services\Admin\EmailListSubscribersTable;
use App\Services\Admin\EmailListTemplates;
use App\Services\Admin\{EmailReportsTable, EmailReportRecipientsTable};
use App\Support\AdminPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmailListsController extends Controller
{
    public function index(Request $request, EmailListTemplates $templates)
    {
        $request->validate(['page' => 'sometimes|integer|min:1']);
        $lists = EmailList::orderBy('id')->paginate(AdminPagination::PER_PAGE);
        $hasFreePick = Piece::free()->exists();
        $recipient = auth('admin')->user()->email;

        return view('admin.pages.subscriptions.lists.index', compact('lists', 'hasFreePick', 'recipient', 'templates'));
    }

    public function reports(Request $request, EmailReportsTable $table)
    {
        return view('admin.pages.reports.index', $table->get($request));
    }

    public function report(Request $request, $list, EmailReportRecipientsTable $table)
    {
        $data = $table->get($request, $list);
        $data['event'] = $table->summary($list);

        return view('admin.pages.reports.show.index', $data);
    }

    public function send(Request $request, EmailList $list)
    {
        $this->assertTemplate($list);
        if ($list->name === EmailList::NEWSLETTER) {
            $campaign = $list->send();
            return back()->with('status', $campaign
                ? 'The monthly PianoLIT newsletter has been queued.'
                : 'No newsletter queued: no new content, no subscribers, or this month was already queued.');
        }
        if ($list->name === EmailList::FREE_PICK && ! Piece::free()->exists())
            return back()->with('error', 'Choose a current free pick before sending this list.');

        if ($list->last_sent_at && $list->last_sent_at->gt(now()->subDay()))
            return back()->with('status', 'This list has recently been sent');

        $list->send();

        return back()->with('status', 'The list email has been queued and will be sent to all subscribers in the background.');
    }

    public function sendTo(Request $request, EmailList $list)
    {
        $this->assertTemplate($list);
        $request->merge(['preview_list_id' => $list->getKey()]);
        $data = $request->validate(['email' => 'required|string|email|max:254']);
        if ($list->name === EmailList::FREE_PICK && ! Piece::free()->exists())
            return back()->withInput()->with('error', 'Choose a current free pick before sending a preview.');

        try {
            \Mail::to($data['email'])->queue($list->mailable('preview'));
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput(['email' => $data['email'], 'preview_list_id' => $list->id])->with('error', 'The preview could not be queued. Please try again.');
        }

        return back()->with('status', 'A preview was queued for ' . $data['email']);
    }

    public function preview(EmailList $list)
    {
        $this->assertTemplate($list);
        abort_if($list->name === EmailList::FREE_PICK && ! Piece::free()->exists(), 422, 'Choose a current free pick before previewing this email.');

        return $list->mailable('preview');
    }

    public function store(Request $request, EmailListTemplates $templates)
    {
        $data = $this->listData($request);
        $request->validate(['name' => Rule::unique('email_lists', 'name')]);
        try {
            $templates->create($data);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withInput()->with('error', 'The list and its email template could not be created. Please check the email views folder is writable and try again.');
        }

        return redirect()->route('admin.subscriptions.lists.index')->with('status', 'The list and its email template have been created.');
    }

    public function edit(Request $request, EmailList $list, EmailListSubscribersTable $table)
    {
        return view('admin.pages.subscriptions.lists.edit', ['list' => $list] + $table->get($request, $list));
    }

    public function update(Request $request, EmailList $list)
    {
        $request->validate(['name' => ['required', 'string', Rule::in([$list->name])]]);
        $list->update($this->listData($request));

        return back()->with('status', 'This list has been updated.');
    }

    public function status(Request $request, EmailList $list)
    {
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

        return redirect()->route('admin.subscriptions.lists.index')->with('status', 'The list has been deleted');
    }

    public function destroyReport(Request $request, $list_id, EmailReportsTable $table)
    {
        $filters = $table->filters($request);
        $this->removeReports([$list_id], true);

        return $table->redirectAfterDeletion($filters)->with('status', 'The report has been deleted.');
    }

    public function destroyManyReports(Request $request, EmailReportsTable $table)
    {
        $filters = $table->filters($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:96',
            'ids.*' => 'required|string|max:255|distinct',
        ]);
        $this->removeReports($data['ids']);

        return $table->redirectAfterDeletion($filters)->with('status', count($data['ids']).' '.str_plural('report', count($data['ids'])).' deleted.');
    }

    private function removeReports(array $ids, $allowLegacy = false)
    {
        DB::transaction(function () use ($ids, $allowLegacy) {
            $reports = EmailCampaignReport::whereIn('list_id', $ids)->orderBy('id')->lockForUpdate()->get(['id']);
            if ($reports->count() !== count($ids)) {
                // A direct historical report may predate summary backfilling.
                if ($allowLegacy) {
                    abort_unless(EmailLog::whereIn('list_id', $ids)->exists(), 404);
                } else {
                    throw ValidationException::withMessages(['ids' => 'Some selected reports no longer exist. Refresh the page and try again.']);
                }
            }
            EmailLog::whereIn('list_id', $ids)->delete();
            EmailCampaignReport::whereIn('list_id', $ids)->delete();
        });
    }

    private function assertTemplate(EmailList $list)
    {
        abort_unless(app(EmailListTemplates::class)->exists($list), 422, 'The email template for this list is missing.');
    }

    private function listData(Request $request)
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'not_regex:/[[:cntrl:]]/'],
            'description' => 'required|string|max:255',
        ]);
    }
}
