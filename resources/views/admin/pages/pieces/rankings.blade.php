<div id="{{$ranking}}-modal" class="modal fade" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Highlights from {{strtoupper($ranking)}} Levels (Syllabus {{(new \App\Services\Rankings)->year($ranking)}})</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="accordion" id="accordion-{{$ranking}}">
          @foreach( (new \App\Services\Rankings)->get($ranking) as $list)
          <div class="card">
            <div class="card-header border-0 rounded mb-1 cursor-pointer" data-toggle="collapse" data-target="#{{$ranking}}-collapse-{{$loop->iteration}}">
              <h6 class="mb-0">Level {{$loop->iteration}}</h6>
            </div>
            <div id="{{$ranking}}-collapse-{{$loop->iteration}}" class="collapse mb-2" data-parent="#accordion-{{$ranking}}">
            @foreach($list as $name => $composer)
              <div class="px-2 py-1 {{$loop->last ? null : 'border-bottom'}}"><small>
                <div class="badge badge-light mr-1">{{$loop->iteration}}</div>
                <a target="_blank" href="{{youtube($name . ' by ' . $composer)}}" class="align-middle link-blue"><strong>{{$name}}</strong> by {{$composer}}</small></a>
              </div>
            @endforeach
            </div>
          </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</div>