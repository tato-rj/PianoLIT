<li class="nav-item dropdown mx-2">
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown">
    {{$label}}
    @fa(['icon' => 'chevron-down', 'mr' => 0, 'styles' => 'font-size: 72%'])
  </a>
  <div class="dropdown-menu py-2 px-3 rounded animated fadeInUp" style="right: 1px!important">
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