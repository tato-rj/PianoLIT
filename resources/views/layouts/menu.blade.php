<nav class="navbar navbar-expand-lg navbar-light py-5">
  <a class="navbar-brand" href="{{route('home')}}">
      <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 50px" class="mr-2">
      <h3 class="font-primary align-middle m-0 d-none d-sm-inline-block"><span>PianoLIT</span></h3>
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item mx-2">
        <a class="nav-link" href="{{route('posts.index')}}">Blog</a>
      </li>
      <li class="nav-item mx-2">
        <a class="nav-link" href="{{route('youtube')}}" target="_blank">Youtube</a>
      </li>
      <li class="nav-item mx-2">
        <button class="nav-link bg-transparent border-0 show-overlay" data-target="#search-overlay"><i class="fas fa-search"></i><span class="ml-2 d-inline-block d-sm-none">Search here</span></button>
      </li>
    </ul>
  </div>
</nav>