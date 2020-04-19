<ul class="nav nav-tabs" id="{{$name}}-tabs" role="tablist">
  @foreach($headers as $header)
  <li class="nav-item">
    <a class="nav-link {{$loop->first ? 'active' : null}}" id="{{str_slug($header)}}-tab" data-toggle="tab" href="#{{str_slug($header)}}" role="tab" aria-controls="{{str_slug($header)}}" aria-selected="true">{{$header}}</a>
  </li>  
  @endforeach
</ul>
<div class="tab-content" id="{{$name}}-panels">
  @foreach($views as $view)
  <div class="tab-pane fade {{$loop->first ? 'show active' : null}}" id="{{str_slug($headers[$loop->index])}}" role="tabpanel" aria-labelledby="{{str_slug($headers[$loop->index])}}-tab">
    @include($view)
  </div>
  @endforeach
</div>