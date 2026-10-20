@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'title' => $list->name, 'subtitle' => number_format($list->subscribers_count).' '.str_plural('subscriber', $list->subscribers_count).' on this list.',
        'breadcrumbs' => [['label' => 'Email lists', 'url' => route('admin.subscriptions.lists.index')]],
    ])
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.subscriptions.export', ['type' => 'txt', 'list_id' => $list->id]) }}" target="_blank" rel="noopener noreferrer">@icon('file-text')Export emails</a>
        <button type="button" class="btn btn-sm btn-outline-danger" data-url="{{ route('admin.subscriptions.lists.destroy', $list) }}" data-bs-toggle="modal" data-bs-target="#delete-modal">@icon('trash-2')Delete list</button>
    @endcomponent

    <form method="POST" action="{{ route('admin.subscriptions.lists.update', $list) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="name" value="{{ $list->name }}">
        @textarea(['bag' => 'default', 'id' => 'list-description', 'name' => 'description', 'label' => 'List description', 'value' => $list->description, 'rows' => 3, 'limit' => 255])
        @component('admin.components.table-save-actions', ['status' => 'Save the description after editing.'])
            <button type="submit" class="btn btn-primary">@icon('check')Save changes</button>
        @endcomponent
    </form>

    @component('admin.components.table', [
        'id' => 'list-subscribers', 'label' => 'List subscribers', 'items' => $subscriptions,
        'action' => route('admin.subscriptions.lists.edit', $list), 'state' => $filters,
        'search' => ['label' => 'Find a subscriber', 'placeholder' => 'Email, origin, form or subscriber ID'],
        'filters' => [['name' => 'status', 'label' => 'Subscription', 'options' => \App\Services\Admin\EmailListSubscribersTable::STATUSES]],
        'sorts' => \App\Services\Admin\SubscriptionsTable::SORTS,
        'columns' => [
            ['label' => 'Date', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Email', 'sort' => ['asc' => 'email_asc', 'desc' => 'email_desc']],
            ['label' => 'Origin', 'sort' => ['asc' => 'origin_asc', 'desc' => 'origin_desc']],
            'On this list',
        ],
        'emptyTitle' => 'No matching subscribers', 'emptyDescription' => 'Try another search or subscription filter.',
    ])
        @foreach($subscriptions as $item)
        <tr>
            <td class="text-nowrap">@if($item->created_at)<time datetime="{{ $item->created_at->toIso8601String() }}">{{ $item->created_at->toFormattedDateString() }}</time>@endif</td>
            <td><span class="admin-table__primary">{{ $item->email }}</span></td>
            <td class="admin-table__account">{{ $item->origin_url ?: '—' }}</td>
            <td>
                <form method="POST" action="{{ route('admin.subscriptions.lists.status', $list) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="subscriberId" value="{{ $item->id }}">
                    <input type="hidden" name="enabled" value="0">
                    @toggle(['toggle' => (bool) $item->list_subscribed, 'autoToggle' => false, 'name' => 'enabled', 'inputValue' => '1', 'ariaLabel' => 'Subscribe '.$item->email.' to '.$list->name, 'submitOnChange' => true])
                    <noscript><button type="submit" class="btn btn-sm btn-outline-secondary">Save</button></noscript>
                </form>
            </td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@endsection
