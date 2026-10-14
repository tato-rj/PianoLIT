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
                    <thead><tr><th scope="col">Submitted</th><th scope="col">Rating</th><th scope="col">Message</th><th scope="col">From</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse($submissions as $submission)
                        <tr>
                            <td class="text-nowrap"><time datetime="{{ $submission->created_at->toIso8601String() }}">{{ $submission->created_at->format('M j, Y') }}<br><small class="text-muted">{{ $submission->created_at->format('g:i A') }}</small></time></td>
                            <td><span class="admin-feedback-rating admin-feedback-rating--{{ $submission->rating }}">{{ \App\FeedbackSubmission::RATINGS[$submission->rating] }}</span></td>
                            <td class="admin-feedback-message"><div class="admin-feedback-message-text">@if($submission->message !== null && $submission->message !== ''){{ $submission->message }}@else<span class="text-muted">No message</span>@endif</div>
                                @if($submission->context)
                                <details class="small mt-2 text-muted">
                                    <summary>Context</summary>
                                    <dl class="mt-2 mb-0">
                                        @if(!empty($submission->context['url']))<dt>Page</dt><dd class="text-break"><a href="{{ $submission->context['url'] }}" target="_blank" rel="noopener noreferrer">{{ $submission->context['url'] }}</a></dd>@endif
                                        @if(!empty($submission->context['route']))<dt>Route</dt><dd>{{ $submission->context['route'] }}</dd>@endif
                                        @if(!empty($submission->context['piece_id']))<dt>Piece</dt><dd><a href="{{ route('webapp.pieces.show', $submission->context['piece_id']) }}" target="_blank" rel="noopener noreferrer">#{{ $submission->context['piece_id'] }}</a></dd>@endif
                                        @if(!empty($submission->context['composer_id']))<dt>Composer</dt><dd><a href="{{ route('webapp.composers.show', $submission->context['composer_id']) }}" target="_blank" rel="noopener noreferrer">#{{ $submission->context['composer_id'] }}</a></dd>@endif
                                        <dt>Device</dt><dd>{{ ucfirst($submission->context['device_type'] ?? 'Unknown') }} · {{ $submission->context['browser'] ?? 'Unknown' }} · {{ $submission->context['os'] ?? 'Unknown' }}</dd>
                                        @if($submission->user_id)<dt>Account</dt><dd>#{{ $submission->user_id }}</dd>@endif
                                    </dl>
                                </details>
                                @endif
                            </td>
                            <td>@if($submission->user)<a href="{{ route('admin.users.show', $submission->user_id) }}">{{ trim($submission->user->first_name.' '.$submission->user->last_name) }}</a><br><small class="text-muted">{{ $submission->user->email }}</small>@else<span class="text-muted">Guest</span>@endif</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.feedback.destroy', ['feedback' => $submission, 'rating' => $rating, 'page' => $submissions->currentPage()]) }}" class="m-0">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-raw text-muted p-2" aria-label="Delete feedback" title="Delete feedback">@icon('trash-2', ['mr' => 0])</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No feedback{{ $rating ? ' with this rating' : '' }} yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $submissions->links() }}
        </div>
    </section>
</div></div>
@endsection
