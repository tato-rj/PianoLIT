@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid">
    @include('admin.components.page.title', ['title' => 'Synthesia Requests', 'subtitle' => 'Manage Synthesia videos requested by users.'])
    @component('admin.components.table', [
        'id' => 'synthesia-requests-table', 'label' => 'Synthesia Requests', 'items' => $requests,
        'action' => route('admin.synthesia-requests.index'), 'state' => $filters,
        'search' => ['label' => 'Find a request', 'placeholder' => 'Piece, composer or user'],
        'filters' => [['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All statuses', 'pending' => 'Pending', 'published' => 'Published']]],
        'sorts' => \App\Services\Admin\SynthesiaRequestsTable::SORTS,
        'showReset' => $filters['search'] !== '' || !empty($filters['status']) || $filters['sort'] !== 'date_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
        'resetUrl' => route('admin.synthesia-requests.index'),
        'columns' => [
            ['label' => 'Date requested', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Piece', 'sort' => ['asc' => 'piece_asc', 'desc' => 'piece_desc']],
            ['label' => 'User', 'sort' => ['asc' => 'user_asc', 'desc' => 'user_desc']],
            ['label' => 'Status', 'sort' => ['asc' => 'published_asc', 'desc' => 'published_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'emptyTitle' => 'No matching Synthesia requests',
        'emptyDescription' => 'Requests will appear here when users submit them. Try changing any active filters.',
    ])
        @foreach($requests as $synthesiaRequest)
        <tr>
            <td class="text-nowrap">{{ $synthesiaRequest->created_at ? $synthesiaRequest->created_at->toFormattedDateString() : 'Date not recorded' }}</td>
            <td><span class="admin-table__primary">{{ $synthesiaRequest->piece ? $synthesiaRequest->piece->medium_name_with_composer : 'Piece unavailable' }}</span></td>
            <td>@include('admin.pages.synthesia-requests.table.user')</td>
            <td class="text-nowrap">
                @if($synthesiaRequest->isPublished())
                    <span class="admin-table__badge admin-table__badge--green">Published</span>
                    <small>{{ $synthesiaRequest->published_at->toFormattedDateString() }}</small>
                @else
                    <span class="admin-table__badge admin-table__badge--warning">Pending</span>
                @endif
            </td>
            <td>@include('admin.pages.synthesia-requests.table.actions')</td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@include('admin.components.modals.delete', ['deleteDescription' => 'Delete this Synthesia request?'])
@endsection
