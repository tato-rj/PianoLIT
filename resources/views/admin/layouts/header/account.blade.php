<div class="dropdown admin-account-menu">
  <button class="admin-header-avatar" type="button" id="admin-account-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu for {{auth('admin')->user()->name}}">
    {{mb_substr(auth('admin')->user()->name, 0, 1)}}
  </button>
  <ul class="dropdown-menu dropdown-menu-end admin-account-dropdown" aria-labelledby="admin-account-toggle">
    <li class="admin-account-details"><strong>{{auth('admin')->user()->name}}</strong><small>{{ucfirst(auth('admin')->user()->role)}}</small></li>
    <li><a class="dropdown-item" href="{{route('admin.editors.edit', auth('admin')->id())}}">@icon('user', ['mr' => 0]) My profile</a></li>
    <li><a class="dropdown-item" href="{{route('home')}}" target="_blank" rel="noopener">@icon('external-link', ['mr' => 0]) View website</a></li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <form action="{{route('admin.logout')}}" method="POST">
        @csrf
        <button class="dropdown-item" type="submit">@icon('log-out', ['mr' => 0]) Log out</button>
      </form>
    </li>
  </ul>
</div>
