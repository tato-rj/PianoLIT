@if(onWebApp())
    @include('auth.webapp.register')
@else
    @include('auth.web.register')
@endif