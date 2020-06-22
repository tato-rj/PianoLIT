@empty($raw)
<section id="webapp-banner" class="px-2 py-2 border-bottom mb-2 text-center alert-blue border-0" style="display: none;">
  Find your next favorite piece in our WebApp! <a target="_blank" href="{{route('webapp.discover')}}" class="link-none"><strong><u>Login here</u></strong></a>
</section>

<header class="container">
    @include('layouts.menu')
</header>
@endempty