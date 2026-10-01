<div class="card border-0 mb-2 {{$loop->first && $isNew ? 'selected' : null}}">
  <div class="cursor-pointer d-flex align-items-center py-3" data-bs-toggle="collapse" data-bs-target="#{{str_slug($title)}}">
    <h6 class="m-0 number text-grey bg-light rounded-circle d-flex flex-center me-2" style="width: 22px; height: 22px">{{$loop->iteration}}</h6>
    <h6 class="m-0 title text-muted">
      {{$title}}<span class="ms-1 text-grey"><small>({{$count}} questions)</small></span>
    </h6>
  </div>

  <div id="{{str_slug($title)}}" class="collapse {{$loop->first && $isNew ? 'show' : null}}" data-bs-parent="#steps">
    <p class="text-danger required-alert" style="display: none;"><small><strong>All fields marked with an asterisk (*) are required</strong></small></p>
    <div class="card-body py-1 border-start border-1x" style="margin-left: .6rem!important;">
      <div class="text-danger mb-2 required-highlight" style="display: none;"><small>@icon('triangle-alert', ['mr' => 1])All fields with an asterisk * are required</small></div>
      {{$slot}}
    </div>
  </div>
</div>