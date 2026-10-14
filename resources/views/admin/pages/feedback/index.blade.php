@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper"><div class="container-fluid admin-feedback">
    @include('admin.components.page.title', ['icon' => 'message-circle-more', 'title' => 'Feedback', 'subtitle' => 'What visitors and members think of their PianoLIT experience.'])
    <section class="card">
        <div class="card-body">
            <form method="GET" class="admin-feedback-filters">
                <label for="feedback-rating">Rating</label>
                <select id="feedback-rating" name="rating" class="form-select">
                    <option value="">All ratings</option>
                    @foreach(\App\FeedbackSubmission::RATINGS as $value => $label)
                    <option value="{{ $value }}" @if($rating === $value) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
                <button class="btn btn-secondary" type="submit">Filter</button>
                <span class="text-muted small">{{ number_format($submissions->total()) }} {{ $submissions->total() === 1 ? 'submission' : 'submissions' }}</span>
            </form>
            <p class="small text-muted">Times shown in {{ config('app.timezone') }}.</p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th scope="col">Submitted</th><th scope="col">Rating</th><th scope="col">Message</th><th scope="col">From</th></tr></thead>
                    <tbody>
                        @forelse($submissions as $submission)
                        <tr>
                            <td class="text-nowrap"><time datetime="{{ $submission->created_at->toIso8601String() }}">{{ $submission->created_at->format('M j, Y') }}<br><small class="text-muted">{{ $submission->created_at->format('g:i A') }}</small></time></td>
                            <td><span class="admin-feedback-rating admin-feedback-rating--{{ $submission->rating }}">{{ \App\FeedbackSubmission::RATINGS[$submission->rating] }}</span></td>
                            <td class="admin-feedback-message">@if($submission->message !== null && $submission->message !== ''){{ $submission->message }}@else<span class="text-muted">No message</span>@endif</td>
                            <td>@if($submission->user)<a href="{{ route('admin.users.show', $submission->user_id) }}">{{ trim($submission->user->first_name.' '.$submission->user->last_name) }}</a><br><small class="text-muted">{{ $submission->user->email }}</small>@else<span class="text-muted">Guest</span>@endif</td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted py-5">No feedback{{ $rating ? ' with this rating' : '' }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $submissions->links() }}
        </div>
    </section>
</div></div>
@endsection
