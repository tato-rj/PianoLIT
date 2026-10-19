<?php

namespace App\Services\Admin;

use App\EmailList;
use App\Support\AdminTableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmailListSubscribersTable
{
    const STATUSES = ['all' => 'All subscribers', 'subscribed' => 'On this list', 'unsubscribed' => 'Not on this list'];

    public function get(Request $request, EmailList $list)
    {
        $filters = AdminTableFilters::fromRequest($request, SubscriptionsTable::SORTS, 'date_desc', [
            'status' => ['nullable', 'string', Rule::in(array_keys(self::STATUSES))],
        ]);
        $filters['status'] = $filters['status'] ?? 'all';
        $membership = DB::table('email_list_subscription')->selectRaw('1')
            ->whereColumn('subscription_id', 'subscriptions.id')->where('email_list_id', $list->id);
        $query = (new SubscriptionsTable)->query($filters)->selectSub($membership->limit(1), 'list_subscribed');
        if ($filters['status'] !== 'all') {
            $query->whereExists(function ($query) use ($list) {
                $query->selectRaw('1')->from('email_list_subscription')
                    ->whereColumn('subscription_id', 'subscriptions.id')->where('email_list_id', $list->id);
            }, 'and', $filters['status'] === 'unsubscribed');
        }
        $subscriptions = $query->paginate($filters['per_page'], ['*'], 'page', $filters['page'] ?? 1)
            ->appends(AdminTableFilters::query(array_diff_key($filters, ['page' => true])));

        return compact('subscriptions', 'filters');
    }
}
