@if(onWebApp())
    @include('auth.webapp.login')
@else
    @include('auth.web.login')
@endif