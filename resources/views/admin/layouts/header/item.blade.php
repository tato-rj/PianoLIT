@php
  $hasDropdown = !empty($dropdown);
  $activeRoute = $hasDropdown ? collect($dropdown)->filter(function ($link) {
    if (empty($link['route'])) return false;
    $destination = strtok($link['route'], '?');
    return url()->current() === $destination || \Illuminate\Support\Str::startsWith(url()->current(), $destination.'/');
  })->sortByDesc(function ($link) { return strlen($link['route']); })->first() : null;
  $active = $hasDropdown ? (bool) $activeRoute : ($route && url()->current() === strtok($route, '?'));
@endphp
<li class="admin-menu-item">
  <a class="admin-menu-link {{$active ? 'is-active' : ''}} {{$hasDropdown && !$active ? 'collapsed' : ''}}"
    @if($hasDropdown) data-bs-toggle="collapse" role="button" aria-expanded="{{$active ? 'true' : 'false'}}" aria-controls="{{str_slug($name)}}" @elseif($active) aria-current="page" @endif
    href="{{$hasDropdown ? '#'.str_slug($name) : $route}}">
    @icon($icon, ['mr' => 0])
    <span>{{$name}}</span>
    @if($hasDropdown) @icon('chevron-down', ['mr' => 0, 'classes' => 'admin-menu-chevron']) @endif
  </a>
  @if($hasDropdown)
  <ul class="admin-submenu collapse {{$active ? 'show' : ''}}" id="{{str_slug($name)}}" data-bs-parent="#navbarResponsive">
    @foreach($dropdown as $link)
      @if(empty($link))
      <li class="admin-submenu-divider" aria-hidden="true"></li>
      @else
      <li><a class="{{($activeRoute['route'] ?? null) === $link['route'] ? 'is-active' : ''}}" href="{{$link['route']}}" @if(!empty($link['target'])) target="{{$link['target']}}" rel="noopener" @endif @if(($activeRoute['route'] ?? null) === $link['route']) aria-current="page" @endif>{{$link['name']}}</a></li>
      @endif
    @endforeach
  </ul>
  @endif
</li>
