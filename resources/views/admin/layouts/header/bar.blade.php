<aside class="admin-sidebar" aria-label="Administration">
  <div class="admin-sidebar-header">
    <a class="admin-brand" href="{{route('admin.home')}}">
      <img src="{{asset('images/brand/admin-icon.svg')}}" width="32" height="32" alt="">
      <span>Piano<strong>LIT</strong><small>ADMINISTRATION</small></span>
    </a>
    <button class="admin-menu-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle administration menu">@icon('menu', ['mr' => 0])</button>
  </div>
  <nav aria-label="Admin menu">@include('admin.layouts.header.menu')</nav>
</aside>
@include('components.panel.notifications')
