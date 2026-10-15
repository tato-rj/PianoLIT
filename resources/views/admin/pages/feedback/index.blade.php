@extends('admin.layouts.app')

@section('head')
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.css"/>
@endsection

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-feedback">
    @include('admin.components.page.title', ['icon' => 'message-circle-more', 'title' => 'Feedback', 'subtitle' => 'What visitors and members think of their PianoLIT experience.'])
    @component('admin.components.table-card')
            <form method="GET" class="admin-feedback-filters admin-table-filters">
                <label for="feedback-rating">Rating</label>
                <select id="feedback-rating" name="rating" class="form-select">
                    <option value="">All ratings</option>
                    @foreach(\App\FeedbackSubmission::RATINGS as $value => $label)
                    <option value="{{ $value }}" @if($rating === $value) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Filter</button>
                <span class="text-muted small" data-feedback-total>{{ number_format($submissions->total()) }} {{ $submissions->total() === 1 ? 'submission' : 'submissions' }}</span>
            </form>
            <p class="small text-muted">Times shown in {{ config('app.timezone') }}.</p>
            <div class="alert alert-danger" role="alert" data-feedback-error hidden>
                <span data-feedback-error-message>Feedback could not be loaded. Please try again.</span>
                <button type="button" class="btn btn-sm btn-secondary ms-2" data-feedback-retry>Retry</button>
            </div>
            <div class="table-responsive">
                <table id="feedback-table" class="table align-middle w-100" data-page="{{ $submissions->currentPage() }}">
                    <thead><tr><th scope="col">Submitted</th><th scope="col">Rating</th><th scope="col">Message</th><th scope="col">From</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse($submissions as $submission)
                        <tr>
                            <td class="text-nowrap">@include('admin.pages.feedback.table.submitted', ['page' => $submissions->currentPage()])</td>
                            <td>@include('admin.pages.feedback.table.rating', ['page' => $submissions->currentPage()])</td>
                            <td class="admin-feedback-message">@include('admin.pages.feedback.table.message', ['page' => $submissions->currentPage()])</td>
                            <td>@include('admin.pages.feedback.table.sender', ['page' => $submissions->currentPage()])</td>
                            <td class="text-end">@include('admin.pages.feedback.table.actions', ['page' => $submissions->currentPage()])</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No feedback{{ $rating ? ' with this rating' : '' }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div data-feedback-pagination>{{ $submissions->links() }}</div>
    @endcomponent
</div></div>
@endsection

@section('scripts')
<script src="https://cdn.datatables.net/v/bs4/dt-1.10.18/r-2.2.2/datatables.min.js"></script>
<script>
$(function () {
    var $table = $('#feedback-table');
    if (!$.fn.DataTable) return;
    var $error = $('[data-feedback-error]');
    function showError(message) {
        $error.find('[data-feedback-error-message]').text(message);
        $error.prop('hidden', false);
    }
    $table.find('tbody tr:has(td[colspan])').remove();
    $table.on('xhr.dt', function (event, settings, json, xhr) {
        if (xhr !== settings.jqXHR) return true;
        if (!json || json.error) {
            showError('Feedback could not be loaded. Please try again.');
            return true;
        }
        if (Number(json.draw) < settings.iDraw) return true;
        $error.prop('hidden', true);
        $('[data-feedback-total]').text(Number(json.recordsTotal).toLocaleString() + (json.recordsTotal === 1 ? ' submission' : ' submissions'));
    });
    var table = $table.DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        pageLength: {{ \App\Support\AdminPagination::PER_PAGE }},
        lengthMenu: [{{ \App\Support\AdminPagination::PER_PAGE }}, 24, 48, 96],
        displayStart: (Number($table.attr('data-page')) - 1) * {{ \App\Support\AdminPagination::PER_PAGE }},
        order: [[0, 'desc']],
        searchDelay: 350,
        ajax: {url: @json(route('admin.feedback.index', ['rating' => $rating])), timeout: 15000},
        columns: [
            {data: 'submitted', className: 'text-nowrap'},
            {data: 'rating'},
            {data: 'message', className: 'admin-feedback-message'},
            {data: 'sender'},
            {data: 'actions', className: 'text-end', orderable: false, searchable: false}
        ],
        language: {
            processing: '<div class="overlay-pulse"></div>',
            emptyTable: @json('No feedback'.($rating ? ' with this rating' : '').' yet.'),
            searchPlaceholder: 'Message, rating or sender',
            paginate: {next: '&#8594;', previous: '&#8592;'}
        },
        initComplete: function () { $('[data-feedback-pagination]').prop('hidden', true); }
    });
    $('[data-feedback-retry]').on('click', function () { table.ajax.reload(null, false); });
    $table.on('submit', 'form', function (event) {
        event.preventDefault();
        var $form = $(this);
        var $button = $form.find('button[type="submit"]');
        if ($button.prop('disabled')) return;
        $button.prop('disabled', true);
        $.ajax({url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json', timeout: 15000})
            .done(function () {
                table.ajax.reload(function () {
                    var info = table.page.info();
                    if (info.page > 0 && info.page >= info.pages) table.page(Math.max(0, info.pages - 1)).draw('page');
                }, false);
            })
            .fail(function () { showError('Feedback could not be deleted. Please try again or refresh the page.'); })
            .always(function () { $button.prop('disabled', false); });
    });
});
</script>
@endsection
