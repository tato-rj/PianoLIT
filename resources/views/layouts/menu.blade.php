<nav class="navbar navbar-expand-lg navbar-light py-5">
  <a class="navbar-brand" href="#">
      <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 50px" class="mr-2">
      <h2 class="font-primary d-inline-block align-middle m-0">PianoLIT</span></h2>
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
        <a class="nav-link" href="https://www.youtube.com/channel/UCOB67MpdySxyTCZHvWgxHeg" target="_blank">Youtube</a>
      </li>

    </ul>
  </div>
</nav>