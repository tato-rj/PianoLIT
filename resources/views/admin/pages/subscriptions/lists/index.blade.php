@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Email Lists', 'subtitle' => 'Manage Freepick subscribers and send the weekly free pick.',
        'action' => $lists->isEmpty() ? ['label' => 'Create Freepick list', 'modal' => 'add-modal'] : null,
    ])

    @if(!$hasFreePick)
        <div class="alert alert-warning" role="status">Choose a current free pick in Pieces before previewing or sending an email.</div>
    @endif

    @component('admin.components.table', [
        'id' => 'email-lists', 'label' => 'Email lists', 'items' => $lists,
        'columns' => ['List', 'Subscribers', 'Last sent', 'Manage', 'Email'],
        'emptyTitle' => 'No email list yet', 'emptyDescription' => 'Create the Freepick list to start collecting subscribers.',
    ])
        @foreach($lists as $list)
        <tr>
            <td class="admin-table__account"><a class="admin-table__primary" href="{{ route('admin.subscriptions.lists.edit', $list) }}">{{ $list->name }}</a><small>{{ $list->description }}</small></td>
            <td>{{ number_format($list->subscribers_count) }}</td>
            <td class="text-nowrap">@if($list->last_sent_at)<time datetime="{{ $list->last_sent_at->toIso8601String() }}">{{ $list->last_sent_at->format('M j, Y · g:i a') }}</time>@else<span class="text-muted">Never sent</span>@endif</td>
            <td><div class="d-flex flex-wrap gap-2">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.subscriptions.lists.edit', $list) }}">@icon('users')Subscribers</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.subscriptions.export', ['type' => 'txt', 'list_id' => $list->id]) }}" target="_blank" rel="noopener noreferrer">@icon('file-text')Export</a>
                <button type="button" class="btn btn-sm btn-outline-danger delete" data-url="{{ route('admin.subscriptions.lists.destroy', $list) }}" data-bs-toggle="modal" data-bs-target="#delete-modal" aria-label="Delete {{ $list->name }} list">@icon('trash-2', ['mr' => 0])</button>
            </div></td>
            <td><div class="d-flex flex-wrap gap-2">
                @if($hasFreePick)
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.subscriptions.lists.preview', $list) }}" target="_blank" rel="noopener noreferrer">@icon('eye')Preview</a>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#list-send-{{ $list->id }}">@icon('mail')Send email</button>
                @else
                    <span class="admin-table__badge admin-table__badge--muted">Needs a free pick</span>
                @endif
            </div></td>
        </tr>
        @endforeach
    @endcomponent
</div></div>

@foreach($lists as $list)
    @if($hasFreePick) @include('admin.pages.subscriptions.lists.send') @endif
@endforeach
@include('admin.components.modals.delete')
@if($lists->isEmpty()) @include('admin.pages.subscriptions.lists.create') @endif
@endsection
