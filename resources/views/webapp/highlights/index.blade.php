@extends('webapp.layouts.app', ['title' => 'Highlights'])

@section('content')
@include('webapp.layouts.header', ['title' => 'Highlights', 'subtitle' => 'What would you like to play next?'])

@include('webapp.components.sorting', ['disabled' => false])

<section id="pieces-list" class="row mt-3" data-highlights-url="{{ route('webapp.highlights') }}" aria-busy="false">
    @include('webapp.highlights.pieces')
</section>

<div id="highlights-loading" hidden>
    @include('webapp.components.spinner')
</div>
<div id="highlights-empty" class="text-grey text-center pt-5 pb-4" role="status" @if($pieces->isNotEmpty()) hidden @endif>
    @icon('package-open', ['mr' => 0, 'size' => 'lg'])
    <div><strong>Sorry, nothing to show!</strong></div>
</div>
<div id="highlights-error" class="text-center py-4" role="alert" hidden>
    <p>We couldn't load these pieces. Please try again.</p>
    <button id="highlights-retry" type="button" class="btn btn-secondary btn-sm">Try again</button>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/highlights.js') }}"></script>
@endpush
