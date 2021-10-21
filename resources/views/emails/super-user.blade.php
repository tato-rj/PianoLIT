@component('mail::message')
# Hi {{$user->first_name}}

Your account has just been granted super user status, enjoy your free access to PianoLIT!

You can access your profile on both the iOS app or on the WebApp.

Best<br>
Elena from {{ config('app.name') }}
@endcomponent
