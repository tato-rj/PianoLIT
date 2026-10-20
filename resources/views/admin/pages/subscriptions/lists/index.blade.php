@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', [
        'title' => 'Email Lists', 'subtitle' => 'Manage your email lists and subscribers.',
        'action' => ['label' => 'Create a new list', 'modal' => 'add-modal'],
    ])

    @if(!$hasFreePick && $lists->contains('name', \App\EmailList::FREE_PICK))
        <div class="alert alert-warning" role="status">Choose a current free pick in Pieces before previewing or sending an email.</div>
    @endif

    @component('admin.components.table', [
        'id' => 'email-lists', 'label' => 'Email lists', 'items' => $lists, 'pageSizes' => [],
        'columns' => ['List', 'Subscribers', 'Last sent', 'Actions'],
        'emptyTitle' => 'No email list yet', 'emptyDescription' => 'Create a list to start collecting subscribers.',
    ])
        @foreach($lists as $list)
        <tr>
            <td class="admin-table__account"><a class="admin-table__primary" href="{{ route('admin.subscriptions.lists.edit', $list) }}">{{ $list->name }}</a></td>
            <td>{{ number_format($list->subscribers_count) }}</td>
            <td class="text-nowrap">@if($list->last_sent_at)<time datetime="{{ $list->last_sent_at->toIso8601String() }}">{{ $list->last_sent_at->format('M j, Y · g:i a') }}</time>@else<span class="text-muted">Never sent</span>@endif</td>
            <td><div class="d-flex flex-nowrap gap-2 text-nowrap">
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'url' => route('admin.subscriptions.lists.edit', $list)]])
                @if($templates->exists($list) && ($list->name !== \App\EmailList::FREE_PICK || $hasFreePick))
                    @include('admin.components.table-action', ['tableAction' => ['label' => 'Send email', 'icon' => 'mail', 'attributes' => ['data-bs-toggle' => 'modal', 'data-bs-target' => '#list-send-'.$list->id]]])
                @else
                    @include('admin.components.table-action', ['tableAction' => ['label' => 'Send email', 'icon' => 'mail', 'attributes' => ['disabled' => true, 'title' => $templates->exists($list) ? 'Choose a current free pick before sending' : 'The email template for this list is missing']]])
                @endif
            </div></td>
        </tr>
        @endforeach
    @endcomponent
</div></div>

@foreach($lists as $list)
    @if($templates->exists($list) && ($list->name !== \App\EmailList::FREE_PICK || $hasFreePick)) @include('admin.pages.subscriptions.lists.send') @endif
@endforeach
@include('admin.pages.subscriptions.lists.create')
@endsection
