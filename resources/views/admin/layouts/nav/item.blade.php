<li class="nav-item">
  <a class="nav-link {{!empty($dropdown) ? 'nav-link-collapse collapsed' : null}}" 
    data-toggle="{{!empty($dropdown) ? 'collapse' : null}}" 
    href="{{!empty($dropdown) ? '#'.str_slug($name) : $route}}">
    <div>
      <i class="fas fa-{{$icon}} fa-fw"></i>
      <span class="nav-link-text">{{$name}}</span>
    </div>
    @if(!empty($dropdown))
    <div>
      <i class="fas fa-caret-down"></i>
    </div>
    @endif
  </a>
  @if(!empty($dropdown))
  <ul class="sidenav-second-level collapse collapsed" id="{{str_slug($name)}}" data-parent="#navbarResponsive">
    @foreach($dropdown as $link)
    <li>
      <a class="py-2" href="{{$link['route']}}">{{$link['name']}}</a>
    </li>
    @endforeach
  </ul>
  @endif
</li>