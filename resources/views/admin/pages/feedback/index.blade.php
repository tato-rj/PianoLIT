@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-feedback">
    @include('admin.components.page.title', ['icon' => 'message-circle-more', 'title' => 'Feedback', 'subtitle' => 'What visitors and members think of their PianoLIT experience.'])
    @component('admin.components.table', [
        'id' => 'feedback-table', 'label' => 'Feedback', 'items' => $submissions,
        'description' => number_format($submissions->total()).' matching submissions · Times shown in '.config('app.timezone').'.',
        'action' => route('admin.feedback.index'), 'state' => $filters,
        'search' => ['label' => 'Find feedback', 'placeholder' => 'Message, rating or sender'],
        'filters' => [['name' => 'rating', 'label' => 'Rating', 'options' => ['' => 'All ratings'] + \App\FeedbackSubmission::RATINGS]],
        'sorts' => \App\Services\Admin\FeedbackTable::SORTS,
        'columns' => [
            ['label' => 'Submitted', 'sort' => ['asc' => 'date_asc', 'desc' => 'date_desc']],
            ['label' => 'Rating', 'sort' => ['asc' => 'rating_asc', 'desc' => 'rating_desc']],
            ['label' => 'Message', 'class' => 'admin-feedback-message', 'sort' => ['asc' => 'message_asc', 'desc' => 'message_desc']],
            ['label' => 'From', 'sort' => ['asc' => 'sender_asc', 'desc' => 'sender_desc']],
            ['label' => 'Actions', 'hidden' => true],
        ],
        'tableClass' => 'admin-feedback-table',
        'emptyTitle' => $filters['search'] ? 'No matching feedback' : 'No feedback'.($rating ? ' with this rating' : '').' yet.',
        'emptyDescription' => ($filters['search'] || $rating) ? 'Try another search or rating.' : 'Feedback will appear here when someone submits it.',
    ])
        @foreach($submissions as $submission)
        <tr>
            <td class="text-nowrap">@include('admin.pages.feedback.table.submitted')</td>
            <td>@include('admin.pages.feedback.table.rating')</td>
            <td class="admin-feedback-message">@include('admin.pages.feedback.table.message')</td>
            <td>@include('admin.pages.feedback.table.sender')</td>
            <td class="text-end">@include('admin.pages.feedback.table.actions', ['page' => $submissions->currentPage()])</td>
        </tr>
        @endforeach
    @endcomponent
</div></div>
@endsection
