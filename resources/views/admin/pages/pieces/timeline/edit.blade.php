@extends('admin.layouts.app')

@section('content')
<div class="content-wrapper" id="piece-timeline-admin" data-discover-url="{{route('admin.pieces.timeline.discover', $piece)}}" data-save-url="{{route('admin.pieces.timeline.store', $piece)}}" data-piece-id="{{$piece->id}}" data-csrf="{{csrf_token()}}">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'theme' => 'edit', 'title' => 'Timeline · '.$events->count().' '.str_plural('event', $events->count()), 'mb' => 'mb-2',
      'subtitle' => $piece->long_name.' · '.$piece->composer->name,
      'back' => ['Edit piece' => route('admin.pieces.edit', $piece)]
    ])
    <div class="timeline-toolbar card" id="timeline-discovery">
      <div class="timeline-toolbar-context">
        <span class="timeline-piece-name" title="{{$piece->long_name}}">{{$piece->long_name}}</span>
        <nav aria-label="Timeline sections" class="timeline-section-links">
          <a href="#timeline-results">Find events</a>
          <a href="#timeline-saved-section">Saved timeline <span id="timeline-saved-count" class="badge bg-light text-muted">{{$events->count()}}</span></a>
        </nav>
      </div>
      <form id="timeline-search" class="timeline-search-form">
        <div class="timeline-year-field">
          <label for="reference-year">Reference year</label>
          <input id="reference-year" type="number" min="1500" max="{{now()->year}}" required class="form-control form-control-sm" value="{{$piece->composed_in ?: ($piece->published_in ?: '')}}">
        </div>
        <button class="btn btn-default btn-sm" id="timeline-find" type="submit">Find 10 events</button>
        <span class="small text-muted timeline-search-hint">Search nearby years. Save only the events you want.</span>
      </form>
    </div>
    <section id="timeline-results" aria-labelledby="timeline-results-title" class="timeline-admin-section">
      <div id="timeline-results-heading" class="timeline-section-heading" hidden>
        <h6 id="timeline-results-title" class="mb-0">Search results <span id="timeline-candidate-count" class="badge bg-light text-muted">0</span></h6>
        <span class="small text-muted">Choose events to save</span>
      </div>
      <p id="timeline-search-status" class="small text-muted" role="status" aria-live="polite"></p>
      <div id="timeline-candidates" class="timeline-candidate-groups"></div>
      <button type="button" id="timeline-more" class="btn btn-default btn-sm" hidden>Find 10 more</button>
    </section>
    <section id="timeline-saved-section" aria-labelledby="saved-timeline-title" class="timeline-admin-section">
      <div class="timeline-section-heading">
        <h6 id="saved-timeline-title" class="mb-0">Saved timeline</h6>
        <span class="small text-muted">Chronological · open an event to edit</span>
      </div>
      <p id="timeline-empty" class="small text-muted" @if($events->isNotEmpty()) hidden @endif>No events saved yet. Find events above and choose Save event.</p>
      <div id="timeline-saved">
        @foreach($events as $event)
          @include('admin.pages.pieces.timeline.saved')
        @endforeach
      </div>
    </section>
  </div>
</div>
@endsection

@section('scripts')
<script src="{{mix('js/views/piece-timeline-admin.js')}}"></script>
@endsection
