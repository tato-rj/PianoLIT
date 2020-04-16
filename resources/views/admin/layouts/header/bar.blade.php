<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <a class="navbar-brand mr-0" full-load href="{{route('admin.home')}}">
    <img src="{{asset('images/brand/admin-icon.svg')}}" class="mr-2 shadow-sm">Piano<strong>LIT</strong> | Admin
  </a>
  <div>
    <li class="nav-item inline-on-collapse text-muted">
      <a class="nav-link position-relative cursor-pointer notifications-link {{auth()->user()->hasNewNotifications() ? 'active' : null}}" data-toggle="fixed-panel" data-target="#notifications-panel">
        <i class="fas fa-fw fa-bell notification-bell"></i>
        <div class="notifications-count bg-white rounded-circle position-absolute font-weight-bold shadow-sm" style="bottom: -2px; right: 0;">
          <div class="d-flex flex-center w-100 h-100">{{auth()->user()->unreadNotifications->count()}}</div>
        </div>
      </a>
    </li>
    <button class="border-0 navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>

  @include('admin.layouts.header.menu')
</nav>

@include('components.panel.notifications')