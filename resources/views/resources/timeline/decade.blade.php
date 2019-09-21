<div class="card border-0 mb-2">
  <div class="alert-grey px-4 py-3 cursor-pointer border-pill" data-toggle="collapse" data-target="#timeline-{{$decade}}" aria-expanded="true" aria-controls="timeline">
    <h6 class="mb-0 d-flex d-apart">
      <div>{{$decade}}s &middot; <small>{{count($events)}} {{str_plural('event', count($events))}}</small><span class="ml-2 text-grey font-italic t-2"><small>click to show</small></span></div>
      <div><i class="fas fa-caret-down"></i></div>
    </h6>
  </div>
  <div id="timeline-{{$decade}}" class="collapse mb-2" data-parent>
    @foreach($events as $event)
    @include('resources/timeline/event')
    @endforeach
  </div>
</div>