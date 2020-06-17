@empty($raw)
<section class="px-2 py-1 border-bottom mb-2 text-center alert-blue">
  <small>Find your next favorite piece in our WebApp! <a href="{{route('webapp.discover')}}" class="link-none"><strong><u>Login here</u></strong></a></small>
</section>

<header class="container">
    @include('layouts.menu')
</header>
@endempty