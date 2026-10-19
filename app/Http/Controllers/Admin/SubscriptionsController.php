<?php

namespace App\Http\Controllers\Admin;

use App\{Subscription, EmailList, User};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Exports\Subscriptions\SubscriptionExport;
use App\Services\Admin\SubscriptionsTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionsController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'emails' => 'required|string',
            'origin_url' => 'required|string|max:255',
        ]);

    	$emails = $this->clean($request->emails);

        if ($failedEmail = $this->cannotProccess($emails, $request))
            return back()->withErrors(['emails' => $failedEmail . ' is not a valid email.']);

    	return back()->with('status', 'We subscribed or re-activated a total of ' . count($emails) . ' ' . str_plural('email', count($emails)) . '.');
    }

    public function cannotProccess($emails, $request)
    {
        foreach ($emails as $email) {
            if (! valid_email($email))
                return $email;
        }

        foreach ($emails as $email) {
            Subscription::createOrActivate((object) ['email' => $email, 'origin_url' => $request->origin_url], $notifyUser = false);
        }
    }

    public function export(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:members,fans,txt',
            'list_id' => 'required_if:type,txt|integer|min:1|exists:email_lists,id',
        ]);

        $emails = $request->type === 'txt'
            ? EmailList::findOrFail($request->list_id)->subscribers()->orderBy('email')->pluck('email')->all()
            : (new SubscriptionExport)->for($request->type)->get();

        return view('admin.pages.subscriptions.exports.txt', compact('emails'));
    }
    
    public function clean($emails)
    {
    	return explode(',', preg_replace('/\s+/', '', $emails));
    }

    public function findUser(Request $request)
    {
        $subscription = Subscription::findOrFail($request->id);
        $user = User::byEmail($subscription->email)->first();

        return view('admin.pages.subscriptions.user', compact('user'));
    }

    public function destroyMany(Request $request)
    {
        $table = app(SubscriptionsTable::class);
        $filters = $table->filters($request);
        $data = $request->validate([
            'ids' => 'required|array|min:1|max:96',
            'ids.*' => 'required|integer|min:1|distinct',
        ]);
        $count = DB::transaction(function () use ($data) {
            $subscriptions = Subscription::whereIn('id', $data['ids'])->orderBy('id')->lockForUpdate()->get();
            if ($subscriptions->count() !== count($data['ids'])) {
                throw ValidationException::withMessages(['ids' => 'Some selected subscribers no longer exist. Refresh the page and try again.']);
            }
            // Preserve list-detachment events and roll back the entire selection
            // if either detachment or deletion fails.
            foreach ($subscriptions as $subscription) $subscription->delete();

            return $subscriptions->count();
        });

        return $table->redirectAfterDeletion($filters)->with('status', $count.' '.str_plural('email', $count).' removed.');
    }

    public function destroy(Subscription $subscription, Request $request)
    {
        $table = app(SubscriptionsTable::class);
        $filters = $table->filters($request);
        DB::transaction(function () use ($subscription) {
            $subscription->delete();
        });

        return $table->redirectAfterDeletion($filters)->with('status', 'The email has been removed.');
    }
}
