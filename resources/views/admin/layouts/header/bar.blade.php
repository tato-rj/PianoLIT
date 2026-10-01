<nav class="navbar navbar-expand-lg navbar-light fixed-top shadow-sm" id="mainNav">
  <a class="navbar-brand me-0" href="{{route('admin.home')}}">
    <img src="{{asset('images/brand/admin-icon.svg')}}" class="me-2 shadow-sm">Piano<strong>LIT</strong> | Admin
  </a>
  <div>
    <li class="nav-item inline-on-collapse text-muted">
      <a class="nav-link position-relative cursor-pointer notifications-link {{auth()->user()->hasNewNotifications() ? 'active' : null}}" href="#notifications-panel" role="button" aria-label="Notifications" data-bs-toggle="offcanvas" data-bs-target="#notifications-panel" aria-controls="notifications-panel">
        @icon('bell', ['mr' => 0, 'classes' => 'icon-fw notification-bell', 'filled' => true])
        <div class="notifications-count bg-white rounded-circle position-absolute fw-bold shadow-sm" style="bottom: -2px; right: 0;">
          <div class="d-flex flex-center w-100 h-100">{{auth()->user()->unreadNotifications->count()}}</div>
        </div>
      </a>
    </li>
    <li class="nav-item inline-on-collapse">
      <button class="btn-raw navbar-toggler navbar-toggler-right nav-link" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive">
        @icon('menu', ['mr' => 0, 'styles' => 'transform: translateY(1px)'])
        {{-- <span class="navbar-toggler-icon"></span> --}}
      </button>
    </li>
  </div>

  @include('admin.layouts.header.menu')
</nav>

@include('components.panel.notifications')