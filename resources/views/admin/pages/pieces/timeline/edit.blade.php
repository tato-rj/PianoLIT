@extends('admin.layouts.app')

@section('content')
@php
  $isLibrary = $isLibrary ?? false;
  $timelineTitle = $isLibrary ? 'Timeline events' : 'Timeline';
  $timelineContext = $isLibrary ? 'Shared event library' : $piece->long_name;
  $savedLabel = $isLibrary ? 'Saved events' : 'Saved timeline';
  $range = \DB::table('composers')
      ->selectRaw('
          MIN(YEAR(date_of_birth)) as earliest_year,
          MAX(YEAR(date_of_death)) as latest_year
      ')
      ->first();
@endphp
<div class="content-wrapper" id="piece-timeline-admin" data-discover-url="{{$isLibrary ? route('admin.timeline-events.discover') : route('admin.pieces.timeline.discover', $piece)}}" data-save-url="{{$isLibrary ? route('admin.timeline-events.store') : route('admin.pieces.timeline.store', $piece)}}" data-search-key="{{$isLibrary ? 'library' : $piece->id}}" data-title="{{$timelineTitle}}" data-title-count="{{$isLibrary ? 'false' : 'true'}}" data-group-candidates="{{$isLibrary ? 'false' : 'true'}}" data-csrf="{{csrf_token()}}">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'theme' => 'edit', 'title' => $isLibrary ? $timelineTitle : $timelineTitle.' · '.$events->count().' '.str_plural('event', $events->count()), 'mb' => 'mb-2',
      'subtitle' => $isLibrary ? 'Curate historical events for the shared repertoire timeline.' : $piece->long_name.' · '.$piece->composer->name,
      'back' => $isLibrary ? null : ['Edit piece' => route('admin.pieces.edit', $piece)]
    ])
    <div class="timeline-toolbar card" id="timeline-discovery">
      <div class="timeline-toolbar-context">
        <span class="timeline-piece-name" title="{{$timelineContext}}">{{$timelineContext}}</span>
        <nav aria-label="Timeline sections" class="timeline-section-links">
          <a href="#timeline-results">Find events</a>
          <a href="#timeline-saved-section">{{$savedLabel}} <span id="timeline-saved-count" class="badge bg-light text-muted">{{$events->count()}}</span></a>
        </nav>
      </div>

      <form id="timeline-search" class="timeline-search-form">
        <div class="timeline-year-field">
          <label for="reference-year">Reference year</label>
          <input id="reference-year" type="number" min="{{$isLibrary ? 1650 : 1500}}" max="{{$isLibrary ? $range->latest_year : now()->year}}" step="10" required class="form-control form-control-sm" value="{{$isLibrary ? '' : ($piece->composed_in ?: ($piece->published_in ?: ''))}}">
        </div>
        <button class="btn btn-default btn-sm" id="timeline-find" type="submit">Find 10 events</button>
        <span class="small text-muted timeline-search-hint">{{$isLibrary ? 'Search this year through 10 years after it.' : 'Search nearby years.'}} Save only the events you want.</span>
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
      <div id="timeline-candidates" class="{{$isLibrary ? 'timeline-candidates-list' : 'timeline-candidate-groups'}}"></div>
      <button type="button" id="timeline-more" class="btn btn-default btn-sm" hidden>Find 10 more</button>
    </section>
    <section id="timeline-saved-section" aria-labelledby="saved-timeline-title" class="timeline-admin-section">
      <div class="timeline-section-heading">
        <h6 id="saved-timeline-title" class="mb-0">{{$savedLabel}}</h6>
        <span class="small text-muted">{{$isLibrary ? 'Open a decade, then an event to edit' : 'Chronological · open an event to edit'}}</span>
      </div>
      <p id="timeline-empty" class="small text-muted" @if($events->isNotEmpty()) hidden @endif>No events saved yet. Find events above and choose Save event.</p>
      <div id="timeline-saved" data-group-decades="{{$isLibrary ? 'true' : 'false'}}">
        @if($isLibrary)
          @foreach($events->groupBy(function ($event) { return intdiv($event->year, 10) * 10; })->sortKeys() as $decade => $decadeEvents)
            <details class="card timeline-saved-decade" data-decade="{{$decade}}">
              <summary class="timeline-decade-summary">{{$decade}}–{{min(9999, $decade + 9)}} <span class="timeline-decade-count badge bg-light text-muted">{{$decadeEvents->count()}} {{str_plural('event', $decadeEvents->count())}}</span></summary>
              <div class="timeline-decade-events">
                @foreach($decadeEvents as $event)
                  @include('admin.pages.pieces.timeline.saved')
                @endforeach
              </div>
            </details>
          @endforeach
        @else
          @foreach($events as $event)
            @include('admin.pages.pieces.timeline.saved')
          @endforeach
        @endif
      </div>
    </section>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{mix('js/views/piece-timeline-admin.js')}}"></script>
@endsection
