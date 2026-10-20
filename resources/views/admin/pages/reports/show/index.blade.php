@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @component('admin.components.page.title', [
        'breadcrumbs' => [['label' => 'Email reports', 'url' => route('admin.subscriptions.reports.index')]],
        'title' => 'Email report', 'subtitle' => $event->name.' · '.($event->sent_at ? $event->sent_at->format('M j, Y, g:i a') : 'Date unknown').' · '.number_format($event->emails_count).' emails',
    ])
        <button type="button" class="btn btn-sm btn-outline-danger" data-url="{{ route('admin.subscriptions.reports.destroy', $event->list_id) }}" data-bs-toggle="modal" data-bs-target="#delete-modal">@icon('trash-2')Delete report</button>
    @endcomponent

    <div class="row g-3 mb-4">
        @foreach(['delivered_count' => 'Delivered', 'failed_count' => 'Failed', 'opens_count' => 'Opened', 'clicks_count' => 'Clicked'] as $metric => $label)
        <div class="col-6 col-xl-3"><div class="card rounded shadow-light h-100"><div class="card-body">
            <p class="small text-muted mb-2">{{ $label }}</p>
            <strong class="h3 d-block mb-1">{{ percentage($event->$metric, $event->emails_count) }}%</strong>
            <span class="small text-muted">{{ number_format($event->$metric) }} of {{ number_format($event->emails_count) }} emails</span>
        </div></div></div>
        @endforeach
    </div>

    @component('admin.components.table', [
        'id' => 'report-table', 'label' => 'Email recipients', 'title' => 'Recipients', 'items' => $recipients,
        'description' => 'Summary percentages count each email once. Opens and clicks below include repeat activity.',
        'action' => route('admin.subscriptions.reports.show', $event->list_id), 'state' => $filters,
        'search' => ['label' => 'Find a recipient', 'placeholder' => 'Email address'],
        'filters' => [['name' => 'status', 'label' => 'Delivery status', 'options' => \App\Services\Admin\EmailReportRecipientsTable::STATUSES]],
        'sorts' => \App\Services\Admin\EmailReportRecipientsTable::SORTS,
        'columns' => [
            ['label' => 'Status', 'sort' => ['asc' => 'status_asc', 'desc' => 'status_desc']],
            ['label' => 'Recipient', 'sort' => ['asc' => 'recipient_asc', 'desc' => 'recipient_desc']],
            ['label' => 'Delivered at', 'sort' => ['asc' => 'delivered_asc', 'desc' => 'delivered_desc']],
            ['label' => 'Failed at', 'sort' => ['asc' => 'failed_asc', 'desc' => 'failed_desc']],
            ['label' => 'Opened', 'sort' => ['asc' => 'opened_asc', 'desc' => 'opened_desc']],
            ['label' => 'Clicked', 'sort' => ['asc' => 'clicked_asc', 'desc' => 'clicked_desc']],
        ],
        'emptyTitle' => 'No matching recipients', 'emptyDescription' => 'Try another email address or delivery status.',
    ])
        @foreach($recipients as $item)
        <tr>
            <td>@include('admin.pages.reports.show.table.status')</td>
            <td class="admin-table__account"><span class="admin-table__primary">{{ $item->recipient }}</span></td>
            @foreach(['delivered_at', 'failed_at'] as $date)
                <td class="text-nowrap">@if($item->$date)<time datetime="{{ $item->$date->toIso8601String() }}">{{ $item->$date->format('M j, Y') }}<small>{{ $item->$date->format('g:i a') }}</small></time>@else<span class="text-muted">—</span>@endif</td>
            @endforeach
            <td>{{ number_format($item->opened) }}</td>
            <td>{{ number_format($item->clicked) }}</td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@include('admin.components.modals.delete')
@endsection
