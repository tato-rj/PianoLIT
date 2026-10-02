@extends('admin.layouts.app')

@section('content')
@php
  $range = \DB::table('composers')
      ->selectRaw('
          MIN(YEAR(date_of_birth)) as earliest_year,
          MAX(YEAR(date_of_death)) as latest_year
      ')
      ->first();
@endphp
<div class="content-wrapper" id="timeline-events-admin" data-discover-url="{{route('admin.timeline-events.discover')}}" data-save-url="{{route('admin.timeline-events.store')}}" data-csrf="{{csrf_token()}}">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'theme' => 'edit', 'title' => 'Timeline events', 'mb' => 'mb-2',
      'subtitle' => 'Curate historical events for the shared repertoire timeline.'
    ])
    <div class="timeline-toolbar card" id="timeline-discovery">
      <div class="timeline-toolbar-context">
        <span class="timeline-library-name" title="Shared event library">Shared event library</span>
        <nav aria-label="Timeline sections" class="timeline-section-links">
          <a href="#timeline-results">Find events</a>
          <a href="#timeline-saved-section">Saved events <span id="timeline-saved-count" class="badge bg-light text-muted">{{$events->count()}}</span></a>
        </nav>
      </div>

      <form id="timeline-search" class="timeline-search-form">
        <div class="timeline-year-field">
          <label for="reference-year">Reference year</label>
          <input id="reference-year" type="number" min="1650" max="{{$range->latest_year}}" step="10" required class="form-control form-control-sm" value="">
        </div>
        <button class="btn btn-default btn-sm" id="timeline-find" type="submit">Find 10 events</button>
        <span class="small text-muted timeline-search-hint">Search this year through 10 years after it. Save only the events you want.</span>
        <fieldset class="timeline-type-controls">
          <legend>Event types</legend>
          <div class="timeline-type-options">
            @foreach(\App\Services\Timeline\WikimediaDiscovery::TYPES as $type => $label)
            <label class="timeline-type-option" for="timeline-type-{{$type}}"><input class="timeline-type" id="timeline-type-{{$type}}" name="types[]" type="checkbox" value="{{$type}}" checked> {{$label}}</label>
            @endforeach
          </div>
          <span class="small text-muted">Includes births and deaths within each subject. Choose at least one type.</span>
        </fieldset>
      </form>
    </div>
    <section id="timeline-results" aria-labelledby="timeline-results-title" class="timeline-admin-section">
      <div id="timeline-results-heading" class="timeline-section-heading" hidden>
        <h6 id="timeline-results-title" class="mb-0">Search results <span id="timeline-candidate-count" class="badge bg-light text-muted">0</span></h6>
        <span class="small text-muted">Choose events to save</span>
      </div>
      <p id="timeline-search-status" class="small text-muted" role="status" aria-live="polite"></p>
      <div id="timeline-candidates" class="timeline-candidates-list"></div>
      <button type="button" id="timeline-more" class="btn btn-default btn-sm" hidden>Find 10 more</button>
    </section>
    <section id="timeline-saved-section" aria-labelledby="saved-timeline-title" class="timeline-admin-section">
      <div class="timeline-section-heading">
        <h6 id="saved-timeline-title" class="mb-0">Saved events</h6>
        <span class="small text-muted">Open a decade, then an event to edit</span>
      </div>
      <p id="timeline-empty" class="small text-muted" @if($events->isNotEmpty()) hidden @endif>No events saved yet. Find events above and choose Save event.</p>
      <div id="timeline-saved">
        @foreach($events->groupBy(function ($event) { return intdiv($event->year, 10) * 10; })->sortKeys() as $decade => $decadeEvents)
          <details class="card timeline-saved-decade" data-decade="{{$decade}}">
            <summary class="timeline-decade-summary">{{$decade}}–{{min(9999, $decade + 9)}} <span class="timeline-decade-count badge bg-light text-muted">{{$decadeEvents->count()}} {{str_plural('event', $decadeEvents->count())}}</span></summary>
            <div class="timeline-decade-events">
              @foreach($decadeEvents as $event)
                @include('admin.pages.timeline-events.saved')
              @endforeach
            </div>
          </details>
        @endforeach
      </div>
    </section>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{mix('js/views/timeline-events-admin.js')}}"></script>
@endsection
