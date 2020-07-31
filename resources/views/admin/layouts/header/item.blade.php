<li class="nav-item">
  <a class="nav-link {{!empty($dropdown) ? 'nav-link-collapse collapsed' : null}}" 
    data-toggle="{{!empty($dropdown) ? 'collapse' : null}}"  
    href="{{!empty($dropdown) ? '#'.str_slug($name) : $route}}">
    <div>
      @fa(['icon' => $icon, 'mr' => 1])
      <span class="nav-link-text">{{$name}}</span>
    </div>
    @if(!empty($dropdown))
    <div>
      <i class="fas fa-caret-down ml-1"></i>
    </div>
    @endif
  </a>
  @if(!empty($dropdown))
  <ul class="sidenav-second-level collapse collapsed" id="{{str_slug($name)}}" data-parent="#navbarResponsive">
    @foreach($dropdown as $link)
    @if(empty($link))
    <div class="dropdown-divider hide-on-sm"></div>
    @else
    <li>
      <a class="py-2" href="{{$link['route']}}" target="{{$link['target'] ?? null}}">{{$link['name']}}</a>
    </li>
    @endif
    @endforeach
  </ul>
  @endif
</li>