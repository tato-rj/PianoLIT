<li class="nav-item dropdown mx-2">
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{$label}}
  </a>
  <div class="dropdown-menu p-2" style="font-size: .9em" aria-labelledby="navbarDropdown">
    @foreach($groups as $group)
      @if(! empty($group['title']))
        <label style="font-size: .9em" class="m-0 text-grey text-uppercase"><small>{{$group['title']}}</small></label>
      @endif

      @foreach($group['links'] as $label => $url)
        @if(in_array($label, ['logout']))
        @include($url)
        @else
        <a class="nav-link p-0 ml-1" href="{{$url}}">{{$label}}</a>
        @endif
      @endforeach

      @if($loop->count > 1 && ! $loop->last)
        <div class="dropdown-divider"></div>
      @endif
    @endforeach
  </div>
</li>