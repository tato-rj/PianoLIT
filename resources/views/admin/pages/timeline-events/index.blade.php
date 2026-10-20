@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper" id="timeline-events-admin" data-discover-url="{{ route('admin.timeline-events.discover') }}" data-save-url="{{ route('admin.timeline-events.store') }}" data-csrf="{{ csrf_token() }}">
  <div class="container-fluid">
    @component('admin.components.page.title', ['title' => 'Timeline events', 'subtitle' => 'Curate historical events for the shared repertoire timeline.'])
      <a href="#timeline-discovery" class="btn btn-sm btn-dark">@icon('search')Find events</a>
      <a href="#timeline-saved-section" class="btn btn-sm btn-dark">@icon('bookmark')Saved events</a>
    @endcomponent

    @component('admin.components.editor.section', ['id' => 'timeline-discovery', 'sectionId' => 'timeline-discovery', 'title' => 'Find events', 'description' => 'Shared event library', 'class' => 'mb-4'])
      @component('admin.components.filters.form', [
        'id' => 'timeline-search', 'label' => 'Find timeline events', 'action' => route('admin.timeline-events.index'),
        'fields' => [['type' => 'number', 'id' => 'reference-year', 'name' => 'reference_year', 'label' => 'Reference year', 'value' => '', 'min' => 1650, 'max' => $latestYear, 'step' => 10, 'required' => true, 'class' => 'timeline-year-field']],
        'buttons' => [['id' => 'timeline-find', 'label' => 'Find 10 events', 'icon' => 'search']],
      ])
        <fieldset class="timeline-type-controls">
          <legend class="small text-muted mb-2">Event types</legend>
          <div class="timeline-type-options">
            @foreach(\App\Services\Timeline\WikimediaDiscovery::TYPES as $type => $label)
              @include('components.form.checkbox', ['id' => 'timeline-type-'.$type, 'name' => 'types[]', 'value' => $type, 'label' => $label, 'checked' => true, 'inputClasses' => 'timeline-type', 'classes' => 'mb-0'])
            @endforeach
          </div>
        </fieldset>
        @slot('note')
          <p class="small text-muted mb-0 w-100">Search this year through 10 years after it. Includes births and deaths within each subject. Choose at least one type, then save the events you want.</p>
        @endslot
      @endcomponent
    @endcomponent

    <section id="timeline-results" aria-labelledby="timeline-results-title" class="timeline-admin-section">
      <div id="timeline-results-heading" class="timeline-section-heading" hidden>
        <h2 id="timeline-results-title" class="h4 mb-0">Search results <span id="timeline-candidate-count" class="badge bg-light text-muted">0</span></h2>
        <span class="small text-muted">Choose events to save</span>
      </div>
      <p id="timeline-search-status" class="small text-muted" role="status" aria-live="polite"></p>
      <div id="timeline-candidates" class="timeline-candidates-list"></div>
      <button type="button" id="timeline-more" class="btn btn-dark mt-3" hidden>Find 10 more</button>
    </section>

    @component('admin.components.editor.section', ['id' => 'saved-timeline', 'sectionId' => 'timeline-saved-section', 'title' => 'Saved events', 'description' => 'Open a decade, then an event to edit.', 'class' => 'timeline-admin-section'])
      @slot('actions')<span class="small text-muted"><span id="timeline-saved-count">{{ $events->count() }}</span> saved events</span>@endslot
      <p id="timeline-empty" class="small text-muted mb-0" @if($events->isNotEmpty()) hidden @endif>No events saved yet. Find events above and choose Save event.</p>
      <div id="timeline-saved">
        @foreach($events->groupBy(function ($event) { return intdiv($event->year, 10) * 10; })->sortKeys() as $decade => $decadeEvents)
          <details class="timeline-saved-decade" data-decade="{{ $decade }}" @if($decadeEvents->contains(function ($event) use ($errors) { return $errors->getBag('timeline-event-'.$event->id)->any(); })) open @endif>
            <summary class="timeline-decade-summary">{{ $decade }}–{{ min(9999, $decade + 9) }} <span class="timeline-decade-count badge bg-light text-muted">{{ $decadeEvents->count() }} {{ str_plural('event', $decadeEvents->count()) }}</span></summary>
            <div class="timeline-decade-events">
              @foreach($decadeEvents as $event)
                @include('admin.pages.timeline-events.saved')
              @endforeach
            </div>
          </details>
        @endforeach
      </div>
    @endcomponent
  </div>
</div>
@endsection

@section('scripts')
<script src="{{ mix('js/views/timeline-events-admin.js') }}"></script>
@endsection
