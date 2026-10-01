@extends('admin.layouts.app')

@section('head')
<style>
#piece-timeline-admin .timeline-thumbnail { width: 120px; height: 100px; object-fit: cover; }
#piece-timeline-admin .timeline-candidate-image { width: 100%; height: 160px; object-fit: cover; }
</style>
@endsection

@section('content')
@php($eventsCount = $piece->timelineEvents()->count())

<div class="content-wrapper" id="piece-timeline-admin" data-discover-url="{{route('admin.pieces.timeline.discover', $piece)}}" data-save-url="{{route('admin.pieces.timeline.store', $piece)}}" data-piece-id="{{$piece->id}}" data-csrf="{{csrf_token()}}">
  <div class="container-fluid">
    @include('admin.components.page.title', [
      'theme' => 'edit', 'title' => 'Timeline · '.$eventsCount . ' ' . str_plural('event', $eventsCount),
      'subtitle' => $piece->long_name,
      'back' => ['Edit piece' => route('admin.pieces.edit', $piece)]
    ])
    <section class="card mb-4">
      <div class="card-body">
        <form id="timeline-search" class="d-sm-flex align-items-end">
          <div class="me-sm-3 mb-3 mb-sm-0">
            <label for="reference-year" class="text-brand">Reference year</label>
            <input id="reference-year" type="number" min="1500" max="{{now()->year}}" required class="form-control" value="{{$piece->composed_in ?: ($piece->published_in ?: '')}}" placeholder="">
          </div>
          <button class="btn btn-default" id="timeline-find" type="submit">Find 10 events</button>
        </form>
        <p class="text-muted mt-3 mb-0"><small>Choose the search year yourself. Only events you save will appear on this piece's web timeline.</small></p>
        <p id="timeline-search-status" class="mt-3 mb-0" role="status" aria-live="polite"></p>
        <div id="timeline-candidates" class="row mt-3"></div>
        <button type="button" id="timeline-more" class="btn btn-default" hidden>Find 10 more</button>
      </div>
    </section>
    <section aria-labelledby="saved-timeline-title">
      <h5 id="saved-timeline-title" class="mb-3">Saved timeline</h5>
      <p id="timeline-empty" class="text-muted" @if($events->isNotEmpty()) hidden @endif>No historical events saved yet.</p>
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
