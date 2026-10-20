@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', ['title' => 'Email Reports', 'subtitle' => 'Delivery and engagement for each mailing.'])

    @component('admin.components.table', [
        'id' => 'reports-table', 'label' => 'Email reports', 'items' => $reports,
        'description' => number_format($reports->total()).' matching reports. Metrics show percentages and email counts.',
        'action' => route('admin.subscriptions.reports.index'), 'state' => $filters,
        'search' => ['label' => 'Find a report', 'placeholder' => 'List name or mailing ID'],
        'sorts' => \App\Services\Admin\EmailReportsTable::SORTS,
        'columns' => [
            ['label' => 'Select', 'hidden' => true],
            ['label' => 'Sent', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Name', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']],
            ['label' => 'Emails', 'sort' => ['asc' => 'emails_asc', 'desc' => 'emails_desc']],
            ['label' => 'Delivered', 'sort' => ['asc' => 'delivered_asc', 'desc' => 'delivered_desc']],
            ['label' => 'Failed', 'sort' => ['asc' => 'failed_asc', 'desc' => 'failed_desc']],
            ['label' => 'Opened', 'sort' => ['asc' => 'opened_asc', 'desc' => 'opened_desc']],
            ['label' => 'Clicked', 'sort' => ['asc' => 'clicked_asc', 'desc' => 'clicked_desc']],
            'Actions',
        ],
        'emptyTitle' => $filters['search'] ? 'No matching reports' : 'No email reports yet',
        'emptyDescription' => $filters['search'] ? 'Try another list name or mailing ID.' : 'Reports appear here after mailing activity is recorded.',
    ])
        @slot('toolbar')
            @if($reports->count())
                @include('admin.components.table-bulk-delete', ['id' => 'reports-selection', 'tableId' => 'reports-table', 'action' => route('admin.subscriptions.reports.destroy-many', $returnFilters), 'label' => 'reports', 'deleteDescription' => 'Their delivery and engagement history will also be removed.'])
            @endif
        @endslot
        @foreach($reports as $report)
        <tr>
            <td><input type="checkbox" class="form-check-input" name="ids[]" value="{{ $report->list_id }}" form="reports-selection-form" aria-label="Select {{ $report->name }} sent {{ $report->sent_at->format('M j, Y, g:i a') }}"></td>
            <td class="text-nowrap"><time datetime="{{ $report->sent_at->toIso8601String() }}">{{ $report->sent_at->format('M j, Y') }}<small>{{ $report->sent_at->format('g:i a') }}</small></time></td>
            <td class="text-nowrap"><a class="admin-table__primary" href="{{ route('admin.subscriptions.reports.show', $report->list_id) }}">{{ $report->name }}</a></td>
            <td>{{ number_format($report->emails_count) }}</td>
            @foreach(['delivered_count', 'failed_count', 'opens_count', 'clicks_count'] as $metric)
                <td class="text-nowrap"><span class="admin-table__primary">{{ percentage($report->$metric, $report->emails_count) }}%</span><small title="{{ number_format($report->$metric) }} emails">{{ number_format($report->$metric) }}</small></td>
            @endforeach
            <td>@include('admin.components.table-action', ['tableAction' => ['url' => route('admin.subscriptions.reports.show', $report->list_id), 'label' => 'View report', 'icon' => 'eye']])</td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@endsection

@section('scripts')
    <script src="{{ asset('js/components/admin-table-bulk-delete.js') }}" defer></script>
@endsection
