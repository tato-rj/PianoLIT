@component('mail::message')
# Hi {{$request->user->first_name}}

Your tutorial request for <u>{{$request->piece->medium_name_with_composer}}</u> has been received and we'll start working on it right away. We will send you an email soon to let you know when it is ready.

Best,<br>
Elena from {{ config('app.name') }}
@endcomponent
