<div class="d-flex flex-wrap flex-center mb-4">
  @foreach($timeline as $century => $decades)
  <button 
    data-target="#timeline-carousel"
    data-slide-to="{{$loop->index}}" 
    class="timeline-btn m-1 btn btn-teal{{$loop->first ? null : '-outline'}}">{{intval(substr($century, 0, 2)) - 1}}00</button>
  @endforeach
</div>
<div id="timeline-carousel" class="carousel slide" data-interval="false" data-touch="false" data-ride="carousel">
  <div class="carousel-inner">
    @foreach($timeline as $century => $decades)
    <div class="carousel-item {{$loop->first ? 'active' : null}}">
      <div class="accordion" id="timeline-{{$century}}">
        @include('timeline.century')
      </div>
    </div>
    @endforeach
  </div>
</div>